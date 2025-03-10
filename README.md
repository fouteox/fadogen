# Fadogen - Build and deploy applications easily.

Fadogen is a tool that allows you to create applications and deploy them very easily. Think of it as the shadcn of deployment. No tools are required (except ddev).

## Getting Started
The easiest way to run this project is with DDEV, a Docker-based local development environment.

### Prerequisites
- [Install DDEV](https://ddev.readthedocs.io/en/stable/#installation)

### Setup
1. Clone this repository:
   ```bash
    git clone https://github.com/fouteox/fadogen.git
    cd fadogen
    ```

2. Start the application:
    ```bash
    ddev start && ddev launch
    ```
    You can also use Herd, but it is not recommended for technical reasons (port conflict):
    ```bash
    make init
    ```
