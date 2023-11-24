#!/bin/bash
# Script to quickly create sub-theme.

echo '
+------------------------------------------------------------------------+
| With this script you can quickly create a DXPR sub-theme               |
+------------------------------------------------------------------------+
'

foldername="$(basename $PWD)"
parentfoldername="$(basename "$(dirname $PWD)")"

if [ "${foldername}" != "dxpr_theme" ]; then
  echo 'Error: This command should be run from the DXPR Theme root folder (themes/contrib/dxpr_theme).'
  exit
fi

if [ "${parentfoldername}" != "contrib" ]; then
  echo 'Error: The dxpr_theme theme (this folder) should be in the "contrib" folder.'
  exit
fi

echo 'Please enter the name for your theme [e.g. My Custom DXPR Theme]'
read CUSTOM_DXPR_THEME_NAME
if [ -z "${CUSTOM_DXPR_THEME_NAME}" ]; then
  echo 'Error: Please enter a name [e.g. My Custom DXPR Theme]'
  exit
fi

echo 'Please enter the machine name for your theme [e.g. my_custom_dxpr_theme]'
read CUSTOM_DXPR_THEME
if [ -z "${CUSTOM_DXPR_THEME}" ]; then
  echo 'Error: Please enter a machine name [e.g. my_custom_dxpr_theme]'
  exit
fi

if [[ ! -e $(dirname "$(dirname $PWD)")/custom ]]; then
  mkdir $(dirname "$(dirname $PWD)")/custom
fi

cd $(dirname "$(dirname $PWD)")/custom
cp -r $(dirname $PWD)/contrib/dxpr_theme/dxpr_theme_STARTERKIT $CUSTOM_DXPR_THEME
cd $CUSTOM_DXPR_THEME
for file in *dxpr_theme_STARTERKIT.*; do mv $file ${file//dxpr_theme_STARTERKIT/$CUSTOM_DXPR_THEME}; done
for file in config/*/*dxpr_theme_STARTERKIT.*; do mv $file ${file//dxpr_theme_STARTERKIT/$CUSTOM_DXPR_THEME}; done
# Difference of commands is i ''
if [[ "$OSTYPE" == "darwin"* ]]; then
  grep -Rl dxpr_theme_STARTERKIT . | xargs sed -i '' -e "s/dxpr_theme_STARTERKIT/$CUSTOM_DXPR_THEME/"
  sed -i '' -e "s/THEMETITLE/$CUSTOM_DXPR_THEME_NAME/" $CUSTOM_DXPR_THEME.info.yml config/schema/$CUSTOM_DXPR_THEME.schema.yml
else
  grep -Rl dxpr_theme_STARTERKIT . | xargs sed -i -e "s/dxpr_theme_STARTERKIT/$CUSTOM_DXPR_THEME/"
  sed -i -e "s/THEMETITLE/$CUSTOM_DXPR_THEME_NAME/" $CUSTOM_DXPR_THEME.info.yml config/schema/$CUSTOM_DXPR_THEME.schema.yml
fi
echo "# Check the themes/custom folder for your new sub-theme."
