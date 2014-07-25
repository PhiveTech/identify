{
    cat "$2" | gpg --passphrase-fd 0 --decrypt --batch --yes -o "$4" "$3";
}
