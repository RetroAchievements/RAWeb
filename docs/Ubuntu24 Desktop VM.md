
# Setting up a local RAWeb instance using an Ubuntu 24 desktop virtual machine
This guide will lead you step-by-step to setup an Ubuntu 24 desktop virtual machine hosting RAWeb. If you're comfortable with `docker`, you may want to set up RAWeb to run directly from within your host machine instead of setting up a nested virtual machine, and may only need to use the abridged instructions in the root-level README.md.

## Create a new VM
In [Oracle VirtualBox Manager](https://www.virtualbox.org/wiki/Downloads), press the New button.
  - Set Name to `Ubuntu 24 - RAWeb`
  - Use the [Ubuntu 24.04 LTS iso](https://ubuntu.com/download/desktop)
  - Check "Skip Unattended Installation"
  - Recommended settings (adjust down if you need):
    - Increase Memory to 8GB (8192MB)
    - Increase Processors to 4
    - Increase Hard Disk size to 32GB (this should be at least 24GB)
  - Select "Try or Install Ubuntu" and press Enter
  - Just click Next/Skip repeatedly until you reach the "Create your account" page:
    * Enter your my RetroAchievements account name as "Your name".
    * Set computer name to `ubuntu24`
    * Set username and password to the lowercase version of your RetroAchievements account name
      - username should be automatically set for you
    * Uncheck "Require my password to log in"
  - Continue to hit Next/Finish until installation begins.
  - When the installation is finished, the VM will reboot and ask you to remove the installation media.
    It should already be removed (you can check in the Devices menu) and press Enter.
  - Another wizard will appear. Just click through it (declining auto-submitting to Ubuntu).

#### Install Guest Additions
Guest Additions provide drivers for interacting more directly with the host system. They're required to dynamically resize the screen when the host window is maximized, and are generally supposed to improve performance of the virtual machine.
- Open a terminal window 
  - Click the Circle icon in the lower left corner, then select the Terminal icon
  - To make it easier to open the Terminal in the future, right click the icon in the left bar and select "Pin to Dash". I also like to Unpin the App Center and Help icons from the left bar.
- In the terminal window, type:
  ```
  $ sudo apt install bzip2 gcc make perl
  ```
- Enter the password that you selected earlier (probably your username lowercase)
- Press Y to allow the installation to continue (I will omit these steps for future apt install commands)
- Insert the Guest Additions CD image from the Devices menu of the VM window.
- Click on the CD in the sidebar, then the "Run Software" button and press "Run" on the popup dialog.
- Enter your password again.
- When the installation completes, right click on the CD icon and Eject it.
- Power off the VM (Power button in upper right corner, Power button, Power Off, Power Off)  

#### Configure the VM
This enables the ability to copy/paste between the VM and the host machine, and sets up ports allowing access to the web server from the host machine.

Go into the virtual machine settings 
  * General > Advanced > Shared Clipboard = Bidrectional
  * Network > Adapter 1 > Port Forwarding > Add (three times)
      | Name | Protocol | Host IP | Host Port | Guest IP | Guest Port |
      | ---- | -------- | ------- | --------- | -------- | ---------- |
      | Web Server | TCP |  | 64000 | | 64000 |
      | Mailpit    | TCP |  | 64050 | | 64050 |
      | VITE       | TCP |  | 64011 | | 64011 |

## Set up the development environment
Start the VM.  You should be able to maximize the VM window now to get a fullscreen Ubuntu desktop.

Go into settings (Power button in upper right corner, gear icon)
* Power > Screen Blank = Never
* Appearance > Style = Dark
* Close settings

Everything after this point occurs in a terminal window, so open one.

#### Don't require entering password for sudo calls
```
$ sudo visudo
```
Add the following line after all of the group rules (lines that start with %) as only the last matching rule wins.

  _Replace `username` with your username._
   ```
  username ALL=(ALL) NOPASSWD:ALL
   ```
Hit `Ctrl-X` to save and exit
```
$ exit
```
This closes the terminal. Reopen it to pick up the changes.

#### Add git branch to bash prompt
```
$ nano ~/.bashrc
```
At the end of the file add:
```
PS1="${debian_chroot:+($debian_chroot)}\[\033[00;35m\]\u@\h\[\033[02;33m\]\$(git branch 2> /dev/null | sed -e '/^[^*]/d' -e 's/* \(.*\)/ \1/')\[\033[00m\]:\[\033[02;37m\]\w\[\033[00m\]$ "
```
Then run the following command to pick up the changes:
```
$ . ~/.bashrc
```

#### Install git
_Replace `username@gmail.com` with your email address and `username` with your github username._
```
$ sudo apt update
$ sudo apt install git
$ git config --global credential.helper store
$ git config --global user.email username@gmail.com
$ git config --global user.name username
```

#### Install docker
```
$ sudo groupadd docker
$ sudo apt-get update
$ sudo apt-get install ca-certificates curl gnupg
$ sudo install -m 0755 -d /etc/apt/keyrings
$ curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
$ sudo chmod a+r /etc/apt/keyrings/docker.gpg
$ echo \
  "deb [arch="$(dpkg --print-architecture)" signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  "$(. /etc/os-release && echo "$VERSION_CODENAME")" stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
$ sudo apt-get update
$ sudo apt-get install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
$ nano ~/.bash_aliases
$ sudo systemctl status docker
```
If everything was successful, you should see "Active: active (running)" in the output.

#### Checkout the code
 If you haven't already, fork the RAWeb repository into your github space

_Replace `username` with your github username._
```
$ mkdir ~/source
$ cd ~/source
$ git clone https://github.com/username/RAWeb.git
$ cd RAWeb
$ git remote add upstream https://github.com/RetroAchievements/RAWeb.git
$ git fetch upstream
$ git branch --set-upstream-to upstream/master
$ git pull
$ git push origin
  - Username = (github username)
  - Password = (github password or personal access token)
```
This sets your github credentials and ensures your fork is up-to-date. Because we set up a credential helper earlier, you shouldn't need to enter your credentials again unless they change.

_Replace `username` with your **local** username._
```
$ git config --global --add safe.directory /home/username/source/RAWeb
```
This prevents a "dubious ownership" warning after sail installs all its stuff

#### Define an alias for sail
Open the `.bashrc` file:
```
$ nano .bashrc
```
And add the following line:
_Replace `username` with your local username._
```
alias sail='sudo /home/username/source/RAWeb/vendor/bin/sail'
```
Reload it to pick up the change.
```
$ . ~/.bashrc
```

#### Setup local installation
```
$ cp .env.example .env
$ nano .env
```
Find the commented out `DEBUGBAR_ENABLED` setting and uncomment it and set it to `"true"`
Find the commented out `QUEUE_CONNECTION` settings and add a new one that sets `QUEUE_CONNECTION` to `"sync"`
Go to the end of the file and add `RA_PASSWORD_SALT=SaltySaltySaltFace`

```
$ sudo apt install npm
$ sudo npm install -g pnpm
$ sudo add-apt-repository ppa:ondrej/php 
$ sudo apt dist-upgrade -y
$ sudo apt install php8.2 php8.2-curl php8.2-dom php8.2-gmp php8.2-intl php8.2-zip php8.2-sqlite3 php8.2-mbstring php8.2-mysql php8.2-gd
$ curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
$ sudo apt-get install -y nodejs
$ curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
$ sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
$ composer update
$ composer install
$ composer setup
$ sail up -d
$ sail root-shell
 # cd ..
 # chown -R sail:sail html
 # exit
$ sail artisan ra:storage:link
$ php composer.phar install
$ pnpm install
$ pnpm build
$ sudo chmod 777 bootstrap
$ sudo chmod 777 bootstrap/cache
```

#### Initialize the database
```
$ sudo apt install mariadb-client-core
$ mysql --port=64010 --host=127.0.0.1 -uroot -pretroachievements
 > CREATE DATABASE `raweb-seeded`;
 > GRANT ALL PRIVILEGES ON `raweb-seeded`.* TO 'retroachievements'@'%';
 > FLUSH PRIVILEGES;
 > EXIT;
$ nano .env
```
Change the `DB_DATABASE` line to point at the new database we just created.
```
DB_DATABASE=raweb-seeded
```

Now it's time to populate the database. This command will take at least 10 minutes. Just let it run.
```
$ sail artisan migrate:fresh --seed
```

You should be able to access the site now via (http://localhost:64000)
- If the port forwarding is set up correctly, you should also be able to access the site using the same URL from the host machine.

#### Note about running tests
Something about the way sail/docker manages the RAWeb instance causes the log files to be owned by sail and any tests that try to write to the log file will fail with a permissions error. To fix permissions for the tests, run the following command:
```
sudo chmod 666 ~/source/RAWeb/storage/logs/*.log
```
This doesn't seem to impact the running docker instance, but frequently needs to be run when alternating back and forth between using the website and running the tests.

### Install VSCode
This step is optional. You're welcome to use whichever editor that you want.

Open FireFox and download the latest x64 .deb from https://code.visualstudio.com/download
```
$ cd ~/Downloads
$ sudo dpkg -i code_*.deb
$ rm code_*.deb
$ code
```
Enter your password for both prompts.

Right click on the icon in the left bar and "Pin to Dash".
* File > Open Folder... Browse to `source/RAWeb` and select "Open" in the upper right corner.
* File > Preferences > Settings > Workspace > scroll down to Files: Exclude > Add Pattern
    `docker`
    `node_modules`
    `vendor`
