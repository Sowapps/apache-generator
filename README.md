# Apache Generator

The apache generator is a configuration generator for Apache2.
It parses your YAML configuration file to generate virtual host configuration.

## Usage

Usage: a2generate.sh [OPTIONS]

You must run this command as root.

-iPATH, --parse=PATH
	The folder path to find the YAML configuration files to parse
	If missing, application will ask for it

-oPATH, --to=PATH
	The folder path to write the Apache2 configuration files
	If missing, application will ask for it

-h
	Show help

