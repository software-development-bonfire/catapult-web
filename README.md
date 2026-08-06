# Catapult Web

# Installation Steps
1. Clone the project from this repository to any of your directory.
2. Run the following commands:

```bash
copy .env.example .env #edit the content of .env file

composer install
npm install --global yarn && npm install --save laravel-echo pusher-js
php artisan key:generate
php artisan make:schema
php artisan migrate:install
php artisan migrate
php artisan seed:install
php artisan seed && php artisan db:seed
php artisan optimize
#-----Perform import SQL file from mapping
php artisan pos:import-sql
php artisan resolve:api-urls # Make sure to setup CDIS_URL on .ENV
php artisan resolve:path # Make sure to setup CDIS_PATH on .ENV

php artisan set:client_id
php artisan set:branch_code
php artisan passport:install
yarn install && npm install pm2 -g && npm install pm2-windows-startup -g
pm2 start ecosystem.config.js
pm2 save
pm2-startup install
pm2 save
yarn run dev
php artisan serve
```

# For Testing
Run the following commands:
```bash
composer install
yarn install
php artisan serve
yarn run dev
```
# For Developers
1. After cloning, click Git Flow in the SourceTree. Click OK to apply.
2. Use Git Flow to branch out to feature / hotfixes. For bugs, click the 'Branch' button and type 'bug/branch_name' to create the bug branch.

## Reminders for Developers
1. Make a feature branch for every new feature. e.g (feature/branch_name)
2. Make a bug branch for bugs and issues that needs to be fixed. e.g (bug/branch_name)
3. Make a hotfix branch for quick fixes in the 'master' branch. e.g (hotfix/branch_name)

### Pull Requests

1. ALWAYS create a pull request for every branch that needs to be merged in the 'develop' branch.
2. Add 2 reviewers, Jaypee Magsakay (general review) and Christian Detera (for backend).
3. Add appropriate Labels: feature, enhancement, bug.
4. ALWAYS rebase your branch to the latest 'develop' branch and resolve conflicts inside your branch before finalizing your PR.

### Rebasing Conflicts
1. After fixing a conflict during rebasing, stage all the files then Open your terminal, DON'T COMMIT. Type the command to continue the rebase:

```bash
git rebase --continue
```

2. Repeat the steps until the rebase is finished.
3. Force push your branch (if necessary).

When you want to stop and restart the rebasing, type this command:

```bash
git rebase --abort
```

# Catapult Custom Commands
* `php artisan network:resolve`  _#This will resolve common local network issues.
In backgound it execute windows command [ **_netsh int ip reset_** ] and [ **ipconfig /flushdns** ]._
* `php artisan clear:cache`  _#Clear all defined caches such; **sync** and **convert**_

* `php artisan pos:import-sql`  _#Import SQL files from mappings/EBC directory in sequence with per-file validation_.
* `php artisan resolve:api-urls`  _#Update end_point URLs in api_setups table with new domain from .env file_.
* `php artisan resolve:path`  _#Resolve and update CDIS_PATH in file storage and terminal configurations from .env configuration_.
- ``` php artisan pos:upload```  *#automatically upload all files and it depends on the configuration of Terminal File Setup;*

- `php artisan pos:pm2`  *#Simply restart the running pm2, but **optimize** is called to make sure it would take effects if there are configurations in Catapult;*
     **This command is equivalent to;**
```
pm2 stop all
php artisan optimize
pm2 restart all
```

- ``` php artisan pos:pm2 install --with=startup```  *#to install pm2 and automatically save processes inside ecosystem.config.js. This include also installation of pm2-windows-startup*
     **This command is equivalent to;**
```
npm install pm2 -g
npm install pm2-windows-startup -g
pm2 start ecosystem.config.js
pm2 save
pm2-startup install
pm2 save
```
- ```php artisan pos:pm2 uninstall --with=startup```  *#to uninstall pm2, this will automatically remove pm2-startup in registry, stop all processes, then remove configuration and caches*
**This command is equivalent to;**
```
pm2-startup uninstall #Disable startup:
pm2 kill #Kill the daemon process
npm remove pm2 -g #uninstall globally
npm rm -rf ~/.pm2 #Remove all saved configuration and logs:
npm cache clean --force # to clean npm cache
```

- ```php artisan pos:validate```  *#to validate current configuration of Catapult.*

  💡**NOTE**

     - [✔] indicates everything is configured properly or working fine
     - [✖] it means configuration needs to be checked and re-configuration is required 
     - [⚠] is just a warning and can be ignored;

# Catapult Enabling Websockets

💡**NOTE** (Must check the .ENV file if below *IMPORTANT* exist and change if necessary)


_IMPORTANT: This changes is for CDIS pusher configuration_
```
#version 2.1.0.11 + must use updated .env.example from

PUSHER_APP_ID="1355368"
PUSHER_APP_KEY="182c58278217ab48deab"
PUSHER_APP_SECRET="61870452e11b3e2897ba"
PUSHER_APP_CLUSTER=eu

to

CDIS_PUSHER_APP_ID="1355368"
CDIS_PUSHER_APP_KEY="182c58278217ab48deab"
CDIS_PUSHER_APP_SECRET="61870452e11b3e2897ba"
CDIS_PUSHER_APP_CLUSTER=eu
```
_This section of .ENV configuration will be the websocket configuration locally_
```
PUSHER_APP_ID="CATAPULT_20240104"
PUSHER_APP_KEY="kKkRUSBBCKCqrLSYNTbclA"
PUSHER_APP_SECRET="JhcHBfaWQiOiJjYXRhcHVsdCIsIm5h"
PUSHER_APP_CLUSTER=mt1
PUSHER_APP_PORT=6100
```
💡NOTE : To enable the websockets features and its capability, you must consider instruction below;
- Configure firewall rules to allow inbound and outbound connection for port **6100**. Or just run ```allow-ports.bat``` to automatically execute the script allowing the port 6100.
- Run the command ```php artisan websocket:serve --port=6001``` to start websocket server with port *6001*.
     💡**NOTE** (If ```pm2``` already started, there's no need to run this command. This command is included on running the ```pm2```, unlesss it is commented/excluded in the `ecosystem.config.js`)
- 