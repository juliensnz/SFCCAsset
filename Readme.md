# Docker tips

## Download dependencies
`docker run -ti -u www-data -v${HOME}/.composer:/var/www/.composer -v $(pwd):/srv/pim -v ~/.ssh:/var/www/.ssh \ 
-w /srv/pim --rm akeneo/pim-php-dev:4.0 php -d memory_limit=4G /usr/local/bin/composer install`

## Run app console
`docker run -ti -u www-data -v${HOME}/.composer:/var/www/.composer -v $(pwd):/srv/pim -v ~/.ssh:/var/www/.ssh \
-w /srv/pim --rm akeneo/pim-php-dev:4.0 php -d memory_limit=4G /srv/pim/bin/console`


## Export Assets information in a json file. 

`docker run -ti -u www-data -v${HOME}/.composer:/var/www/.composer -v $(pwd):/srv/pim -v ~/.ssh:/var/www/.ssh \
-w /srv/pim --rm akeneo/pim-php-dev:4.0 php -d memory_limit=4G /srv/pim/bin/console app:export:asset  /srv/pim/`