
# Setting up a local RAWeb instance using an Ubuntu 24 desktop virtual machine
This guide will lead you step-by-step to setup an Ubuntu 24 desktop virtual machine hosting RAWeb. If you're comfortable with `docker`, you may want to set up RAWeb to run directly from within your host machine instead of setting up a nested virtual machine, and may only need to use the abridged instructions in the root-level README.md.

## Create a new VM
In [Oracle VirtualBox Manager](https://www.virtualbox.org/wiki/Downloads), press the New button.
  - Set Name to `Ubuntu 24 - RAWeb`
  - Use the [Ubuntu 24.04 LTS iso](https://ubuntu.com/download/desktop)
  - Check "Proceed with Unattended Installation"
* Expand the "Set up unattended guest OS installation" section
  - Set user name and password to lowercase version of your RetroAchievements account name (i.e. "jamiras")
  - Set host name to "ubuntu24"
  - Check "Install Guest Additions"
* Expand the "Specify virtual hardware" section
  - Increase Memory to 8GB (8192MB) - 10GB (10240MB) if you can spare it.
  - Increase Processors to 4
* Expand the "Specify virtual hard disk" section
  - Increase Hard Disk size to 32GB (this should be at least 24GB)
* Press Finish
  - Let the VM boot up.
  - The installer will run for a while.
  - Eventually, it will ask you to restart. I've had issues clicking the Restart button, but you can still restart
    from the menu in the upper right corner.
* After reboot
  - A wizard will appear. Just click through it (declining auto-submitting to Ubuntu).

#### Install Guest Additions
Guest Additions provide drivers for interacting more directly with the host system. They're required to dynamically resize the screen when the host window is maximized, and are generally supposed to improve performance of the virtual machine.
If you can maximize the host window and the VM contents resize to fit, this is already done. Power off the VM, and skip ahead to the next section.
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
  * General > Features > Shared Clipboard = Bidirectional
  * Network > Adapter 1 > Port Forwarding > Add (four times)
      | Name | Protocol | Host IP | Host Port | Guest IP | Guest Port |
      | ---- | -------- | ------- | --------- | -------- | ---------- |
      | Web Server | TCP |  | 64000 | | 64000 |
      | Mailpit    | TCP |  | 64050 | | 64050 |
      | MinIO      | TCP |  | 64040 | | 64040 |
      | VITE       | TCP |  | 64009 | | 64009 |

## Set up the development environment
Start the VM.  You should be able to maximize the VM window now to get a fullscreen Ubuntu desktop.

Go into settings (Power button in upper right corner, gear icon)
* Power > Screen Blank = Never
* Appearance > Style = Dark
* System > Users
  - Click "Unlock" and enter your password.
  - Enable "Automatic Login".
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
$ sudo usermod -aG docker $(whoami)
$ newgrp docker
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
$ sudo systemctl enable docker
$ sudo systemctl status docker
```
If everything was successful, you should see "Active: active (running)" in the output.

Reboot to ensure it's enabled for all terminals.

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
Open the `.bash_aliases` file:
```
$ nano ~/.bash_aliases
```
And add the following line:
_Replace `username` with your local username._
```
alias sail='/home/username/source/RAWeb/vendor/bin/sail'
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
Find the commented out `APP_VERSION=DEV` setting and uncomment it

Find the commented out `DEBUGBAR_ENABLED` setting and uncomment it and set it to `"true"`

Find the commented out `QUEUE_CONNECTION` settings and add a new one that sets `QUEUE_CONNECTION` to `"sync"`

Go to the end of the file and add `RA_PASSWORD_SALT=SaltySaltySaltFace`

```
$ sudo apt install npm
$ sudo npm install -g pnpm
$ sudo add-apt-repository ppa:ondrej/php 
$ sudo apt dist-upgrade -y
$ sudo apt install php8.4 php8.4-curl php8.4-dom php8.4-gmp php8.4-intl php8.4-zip php8.4-sqlite3 php8.4-mbstring php8.4-mysql php8.4-gd
$ curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
$ sudo apt-get install -y nodejs
$ curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
$ sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
$ composer update
$ composer install
$ composer setup
$ sail up -d
$ sail artisan ra:storage:link
$ rm public/fi-build
$ pnpm install
$ pnpm build
$ sail artisan passport:keys --force
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

#### Start Horizon
In a new terminal, run the following command:
```
sail artisan horizon
```
Horizon is a task dispatcher, and is required for many things within the website - most notably maintaining aggregated metrics. If you ever notice numbers not updating after doing things, it's probably because Horizon isn't running.

#### Populate database
Now it's time to populate the database. This command could take 15 minutes or a couple hours (depending on your machine). Just let it run.
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

Right click on the icon in the left bar and "Pin to Dash".
* File > Open Folder... Browse to `source/RAWeb` and select "Open" in the upper right corner.
* File > Preferences > Settings > Workspace > scroll down to Files: Exclude > Add Pattern
    `docker`
    `node_modules`
    `storage`
    `vendor`
