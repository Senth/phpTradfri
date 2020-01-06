#!/bin/bash

INPUT_FILE="/var/www/home.senth.org/schedule_file"

# Only do something if there are commands to run
if [ -f $INPUT_FILE ]; then
	declare -a commands

	# Run commands
	/bin/bash $INPUT_FILE &

	sleep 1

	# Remove file
	rm $INPUT_FILE
fi
