FROM serversideup/ansible:alpine

RUN ansible-galaxy install geerlingguy.docker

WORKDIR /ansible

RUN mkdir -p /ansible/roles /ansible/group_vars

COPY roles/ /ansible/roles/
COPY inventory.yml playbook.yml /ansible/
COPY group_vars/ /ansible/group_vars/

CMD ["sh", "-c", "if [ -f /ansible/config.yml ]; then ansible-playbook -i inventory.yml playbook.yml; else echo 'Erreur: config.yml non trouvé' && exit 1; fi"]