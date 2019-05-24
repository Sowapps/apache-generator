#!/bin/bash

cd "$(dirname "$(readlink -f "$0")")"

phpGenerator="generate-apache2-configuration.php"
isInstall="";
if [ ! -r $phpGenerator ] || [ "$1" == "--install" ]; then
	isInstall="1"
fi;
if [ $isInstall ] || [ "$1" == "--update" ]; then
	version=${2:-latest}
	#version="latest"
	#version="tags/v1.0.0"
	repos="Sowapps/apache-generator"
	latestReleaseData="https://api.github.com/repos/$repos/releases/$version"
	wget -q -O - $(curl -sL "$latestReleaseData" | sed 's/.*"tarball_url": "\(.*\)".*/\1/;t;d') | tar -xzf - -C ./ --strip 1 --recursive-unlink
	echo "Installed $version version from repository $repos.";
	if [ $isInstall ]; then
		echo "Try using argument --help to get more information about usage.";
	fi;
	exit 0;
fi;

php -f "$phpGenerator" -- "$@"
