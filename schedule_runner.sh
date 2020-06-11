#!/bin/bash

INPUT_DIR="/var/www/home.senth.org"
INPUT_FILE_PREFIX="schedule_file"

INPUT_FILES=($(find "$INPUT_DIR" -maxdepth 1 -name "$INPUT_FILE_PREFIX*"))

# Run the commands
for file in "${INPUT_FILES[@]}"; do
	/bin/bash "$file" &> /tmp/error_log
done

sleep 1

# Delete the command/file afterward
for file in "${INPUT_FILES[@]}"; do
	rm "$file"
done
