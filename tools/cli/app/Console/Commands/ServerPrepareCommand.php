<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

final class ServerPrepareCommand extends Command
{
    protected $signature = 'server:prepare';

    protected $description = 'Command description';

    public function handle(): void
    {
        $cloudflareEmail = '';
        $selectedAccountId = '';
        $selectedDomain = '';

        while (true) {
            $responses = form()
                ->text(
                    label: 'Cloudflare Email',
                    default: $cloudflareEmail,
                    required: true,
                    name: 'cloudflare_email'
                )
                ->password(
                    label: 'Cloudflare API Key',
                    required: true,
                    name: 'cloudflare_key'
                )
                ->submit();

            $cloudflareEmail = $responses['cloudflare_email'];
            $cloudflareKey = $responses['cloudflare_key'];

            info('Attempting to verify Cloudflare credentials...');

            try {
                $command = [
                    'curl',
                    '-s',
                    '-X', 'GET',
                    'https://api.cloudflare.com/client/v4/accounts',
                    '-H', "X-Auth-Email: $cloudflareEmail",
                    '-H', "X-Auth-Key: $cloudflareKey",
                    '-H', 'Content-Type: application/json',
                ];

                $process = new Process($command);
                $process->run();

                $response = json_decode($process->getOutput(), true);

                if ($process->isSuccessful() && isset($response['success']) && $response['success'] === true) {
                    info('Cloudflare credentials verified successfully!');
                    if (isset($response['messages'][0]['message'])) {
                        info($response['messages'][0]['message']);
                    }

                    if (isset($response['result']) && is_array($response['result'])) {
                        $accounts = $response['result'];

                        if (empty($accounts)) {
                            warning('No Cloudflare accounts found. Please check your credentials.');

                            continue;
                        }
                        if (count($accounts) === 1) {
                            $selectedAccountId = $accounts[0]['id'];
                            info('Cloudflare account automatically selected: '.$accounts[0]['name']." ($selectedAccountId)");
                        } else {
                            $accountOptions = [];
                            foreach ($accounts as $account) {
                                $accountOptions[$account['id']] = $account['name'];
                            }

                            $selectedAccountId = select(
                                label: 'Select a Cloudflare account',
                                options: $accountOptions
                            );

                            $selectedAccountName = $accountOptions[$selectedAccountId];
                            info("Cloudflare account selected: $selectedAccountName ($selectedAccountId)");
                        }

                        info('Retrieving domains for the selected account...');
                        $command = [
                            'curl',
                            '-s',
                            '-X', 'GET',
                            "https://api.cloudflare.com/client/v4/accounts/$selectedAccountId/registrar/domains",
                            '-H', "X-Auth-Email: $cloudflareEmail",
                            '-H', "X-Auth-Key: $cloudflareKey",
                            '-H', 'Content-Type: application/json',
                        ];

                        $process = new Process($command);
                        $process->run();

                        $domainsResponse = json_decode($process->getOutput(), true);

                        if ($process->isSuccessful() && isset($domainsResponse['success']) && $domainsResponse['success'] === true) {
                            if (isset($domainsResponse['result']) && is_array($domainsResponse['result'])) {
                                $domains = $domainsResponse['result'];

                                if (empty($domains)) {
                                    warning('No domains found for this Cloudflare account. Please add a domain to your account first.');

                                    return;
                                }
                                if (count($domains) === 1) {
                                    $selectedDomain = $domains[0]['name'];
                                    if (confirm("Do you want to use the domain '$selectedDomain'?")) {
                                        info("Domain selected: $selectedDomain");
                                    } else {
                                        warning('Please add another domain to your Cloudflare account.');

                                        return;
                                    }
                                } else {
                                    $domainOptions = [];
                                    foreach ($domains as $domain) {
                                        $domainOptions[$domain['name']] = $domain['name'];
                                    }

                                    $selectedDomain = select(
                                        label: 'Select a domain',
                                        options: $domainOptions
                                    );

                                    info("Domain selected: $selectedDomain");
                                }
                            }
                        } else {
                            $errorMessage = $domainsResponse['errors'][0]['message'] ?? 'Unknown error';
                            error('Failed to retrieve domains');
                            error('Message: '.$errorMessage);

                            continue;
                        }
                    }

                    break;
                }
                $errorMessage = $response['errors'][0]['message'] ?? 'Unknown error';
                error('Failed to verify Cloudflare credentials');
                error('Message: '.$errorMessage);

            } catch (Exception $e) {
                error('Error while verifying Cloudflare credentials: '.$e->getMessage());
            }

            warning('Cloudflare verification failed. Please try again.');
        }

        $this->updateEnvProductionFile($selectedDomain);

        $username = '';
        $host = '';
        $port = 22;

        while (true) {
            $responses = form()
                ->text(
                    label: 'SSH Username',
                    default: $username,
                    required: true,
                    name: 'username'
                )
                ->text(
                    label: 'SSH Server Address',
                    default: $host,
                    required: true,
                    name: 'host'
                )
                ->submit();

            $username = $responses['username'];
            $host = $responses['host'];

            $sshKeyFiles = $this->findSshKeyFiles();

            if (empty($sshKeyFiles)) {
                warning('No SSH key files found in /root/.ssh/');
                warning('Make sure the SSH volume is correctly mounted.');

                exit();
            }
            if (count($sshKeyFiles) === 1) {
                $selectedKey = array_key_first($sshKeyFiles);
                info("SSH key automatically selected: $sshKeyFiles[$selectedKey]");
            } else {
                $selectedKey = select(
                    label: 'Select an SSH key file',
                    options: $sshKeyFiles
                );
            }

            $sshKeyPath = "/root/.ssh/$selectedKey";

            info("Attempting to connect to $username@$host:$port with key $sshKeyPath...");

            try {
                $sshOptions = [
                    'ssh',
                    '-p', $port,
                    '-o', 'StrictHostKeyChecking=accept-new',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-i', $sshKeyPath,
                ];

                $sshOptions[] = "$username@$host";
                $sshOptions[] = 'echo "Connection established successfully!"';

                $process = new Process($sshOptions);
                $process->setTimeout(60);
                $process->run();

                if ($process->isSuccessful()) {
                    info($process->getOutput());
                    info('SSH connection successful!');

                    $archCommand = array_merge($sshOptions);
                    array_pop($archCommand);
                    $archCommand[] = 'uname -m';

                    $archProcess = new Process($archCommand);
                    $archProcess->setTimeout(30);
                    $archProcess->run();

                    $serverArch = null;
                    if ($archProcess->isSuccessful()) {
                        $serverArch = mb_trim($archProcess->getOutput());
                    }

                    $uidCommand = array_merge($sshOptions);
                    array_pop($uidCommand);
                    $uidCommand[] = 'id -u';

                    $uidProcess = new Process($uidCommand);
                    $uidProcess->setTimeout(30);
                    $uidProcess->run();

                    $userUid = null;
                    if ($uidProcess->isSuccessful()) {
                        $userUid = mb_trim($uidProcess->getOutput());
                    }

                    $gidCommand = array_merge($sshOptions);
                    array_pop($gidCommand);
                    $gidCommand[] = 'id -g';

                    $gidProcess = new Process($gidCommand);
                    $gidProcess->setTimeout(30);
                    $gidProcess->run();

                    $userGid = null;
                    if ($gidProcess->isSuccessful()) {
                        $userGid = mb_trim($gidProcess->getOutput());
                    }

                    break;
                }
                error('SSH connection failed');
                error($process->getErrorOutput());

            } catch (Exception $e) {
                error('Error executing SSH command: '.$e->getMessage());
            }

            warning('Connection failed. Please try again.');
        }

        info('Retrieving SSH public keys from the server...');
        $sshKeyscanCommand = [
            'ssh-keyscan',
            '-H',
            $host,
        ];

        $process = new Process($sshKeyscanCommand);
        $process->setTimeout(60);
        $process->run();

        $sshKeyscanResult = null;
        if ($process->isSuccessful()) {
            $rawOutput = $process->getOutput();
            $sshKeyscanResult = mb_trim($rawOutput);
        }

        $privateKeyContent = file_get_contents($sshKeyPath);
        $sshDomain = 'ssh.'.$selectedDomain;

        $configData = [
            'cloudflare' => [
                'email' => $cloudflareEmail,
                'api_key' => $cloudflareKey,
                'account_id' => $selectedAccountId,
                'domain' => $selectedDomain,
                'ssh_domain' => $sshDomain,
            ],
            'raspberry' => [
                'hostname' => $host,
                'user' => $username,
            ],
        ];

        $serverConfigPath = '/app/work/server-config';
        if (! is_dir($serverConfigPath)) {
            mkdir($serverConfigPath, 0755, true);
            info("Creating directory $serverConfigPath");
        }

        $yamlConfig = Yaml::dump($configData);
        file_put_contents("$serverConfigPath/config.yml", $yamlConfig);

        file_put_contents("$serverConfigPath/.env.SSH_KNOWN_HOSTS", $sshKeyscanResult);

        file_put_contents("$serverConfigPath/.env.SSH_PRIVATE_KEY", $privateKeyContent);

        file_put_contents("$serverConfigPath/.env.SSH_REMOTE_HOSTNAME", $sshDomain);

        file_put_contents("$serverConfigPath/.env.SSH_USER", $username);

        if ($serverArch) {
            file_put_contents("$serverConfigPath/.env.SSH_ARCH", $serverArch);
        }

        if ($userUid) {
            file_put_contents("$serverConfigPath/.env.SSH_UID", $userUid);
        }

        if ($userGid) {
            file_put_contents("$serverConfigPath/.env.SSH_GID", $userGid);
        }
    }

    /**
     * Update APP_HOST in .env.production file with selected domain
     */
    private function updateEnvProductionFile(string $domain): void
    {
        if (! is_dir('/app/work')) {
            mkdir('/app/work', 0755, true);
        }

        $envFilePath = '/app/work/.env.production';

        if (! file_exists($envFilePath)) {
            file_put_contents($envFilePath, "APP_HOST=$domain");

            return;
        }

        $envContent = file_get_contents($envFilePath);

        if (preg_match('/^APP_HOST=(.*)$/m', $envContent)) {
            $updatedContent = preg_replace('/^APP_HOST=(.*)$/m', "APP_HOST=$domain", $envContent);
            file_put_contents($envFilePath, $updatedContent);
        } else {
            $envContent .= "\nAPP_HOST=$domain\n";
            file_put_contents($envFilePath, $envContent);
        }

        $process = new Process(['php', 'artisan', 'key:generate', '--env', 'production']);
        $process->setWorkingDirectory('/app/work');
        $process->run();
    }

    private function findSshKeyFiles(): array
    {
        $keyFiles = [];

        if (! is_dir('/root/.ssh')) {
            return $keyFiles;
        }

        $files = scandir('/root/.ssh');

        foreach ($files as $file) {
            $fullPath = "/root/.ssh/$file";

            if (is_dir($fullPath) || str_starts_with($file, '.')) {
                continue;
            }

            $skipExtensions = ['.pub', '.tmp', '.bak', '.old'];
            $skipFiles = ['config', 'known_hosts', 'authorized_keys'];

            $skipFile = false;
            foreach ($skipExtensions as $ext) {
                if (str_ends_with($file, $ext)) {
                    $skipFile = true;
                    break;
                }
            }

            if (in_array($file, $skipFiles)) {
                $skipFile = true;
            }

            if ($skipFile) {
                continue;
            }

            $keyFiles[$file] = $file;
        }

        return $keyFiles;
    }
}
