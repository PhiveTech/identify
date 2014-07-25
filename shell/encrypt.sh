# echo "$1";
# echo "";
{
    gpg --encrypt --always-trust --yes -o "$3" --recipient "$1" "$2";
}
