# FlexibleMink
Mink extension with patient assertions and object storage

Installing Development Pre-Requisites
-------------------------------------

Install [Docker Toolbox](https://www.docker.com/toolbox).

Once installed, initialize and start the VM by running the "Docker Quickstart Terminal" application located in `/Applications/Docker`.

Add the following to your shell profile:

```
eval $(docker-machine env default)
```

Some profile locations for different shells:

* general: `~/.profile`
* bash: `~/.bashrc`
* zsh: `~/.zshrc`

Reload your profile to apply the changes, e.g.:

```bash
. ~/.profile
```

You will also want to ensure that `./bin` is in your `$PATH` and is the highest priority. You can do so by adding the
following to the end of the same profile mentioned above:

```
export PATH=./bin:$PATH
```

Installing The Project for Development
--------------------------------------

Initialize the project:

```bash
composer install
```
