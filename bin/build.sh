#!/usr/bin/env bash

set -e

echo "Cleaning package.."


while read -r file export; do
  # Load file to be removed.
  echo "Removing $file"
  rm -rf $file
done < .gitattributes
