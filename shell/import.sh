# echo "$1";
# echo "";
{
    if [ "$2" == "1" ]; then
        gpg --allow-secret-key-import --import "$1";
    else
        gpg --import "$1";
    fi
}
