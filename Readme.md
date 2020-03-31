# Installation

    composer install

# Setup

You need to copy the [.env.dist](https://symfony.com/doc/current/components/dotenv.html) file:
```bash
cp .env.dist .env
```

Then open `.env` to define the needed configuration vars:
- `AKENEO_API_BASE_URI` refers to the URL of your PIM Enterprise Edition, used for API calls.
   For example, `http://localhost:80`.
   If you use Docker, set this value to `http://httpd:80` (or `https://httpd:443` if you use SSL).
- `APP_ENV` refers to the `APP_ENV` of your PIM Enterprise Edition, used for direct bask calls.
   Set it to `prod`, `prod_onprem_paas`...


# Usage

First you need to extract your assets to a single json file:

    bin/console app:export:asset assets.json

Second, to generate the product xml file you need to launch this command:

    bin/console app:export:product assets.json myAssetCollectionAttributCode

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


# Distribution

`tar cvfz SFCCAsset.tgz --exclude='SFCCAsset/var' --exclude='SFCCAsset/vendor' --exclude='SFCCAsset/.git' --exclude='SFCCAsset/.idea' --exclude='SFCCAsset/.gitignore' ../SFCCAsset
`
