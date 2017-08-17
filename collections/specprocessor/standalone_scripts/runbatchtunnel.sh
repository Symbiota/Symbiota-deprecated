#!/bin/bash

echo "Creating ssh tunnel..."
# If you setup a Key authenication, remove password 
ssh -L 3306:localhost:3306 username@host

echo "Running batch scripts..."
php Batchimagehandler.php

echo "logout of ssh tunnel..."
logout

echo "Done!"