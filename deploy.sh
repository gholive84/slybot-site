#!/bin/bash
REPO="/home/u492702861/slybot-site"
WP="/home/u492702861/domains/slybot.com.br/public_html"
cd $REPO && git pull
rsync -a --delete $REPO/theme/                          $WP/wp-content/themes/hello-theme-child-master/
rsync -a --delete $REPO/plugins/slybot-course/          $WP/wp-content/plugins/slybot-course/
rsync -a --delete $REPO/plugins/slybot-license-manager/ $WP/wp-content/plugins/slybot-license-manager/
rsync -a --delete $REPO/plugins/slybot-strategies/      $WP/wp-content/plugins/slybot-strategies/
echo "Deploy concluído."
