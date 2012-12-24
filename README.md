Simple Authorization Class
==========================

A Login statement is started with the line

$class->login(USER,PASS,KEEP_LOGGED_IN("On" or "Off"));

To logout, and return to the index

$class->logout();

----------------------

Flags are defined in the database as a Json Array String.
For example:

{"basic":true,"upload":true,"browse":true,"system":false,"root":false}

This line says the the user has the following Flags:
Basic
Upload
Browse

But Not the System or root flags.

The same can be done without including the system and root flags in the array:

{"basic":true,"upload":true,"browse":true}
User has:
Basic
Upload
Browse

If a user has the root flag, that means that they have ALL flags.

You can create as many flags as you want. To check if a user has a flag you issue the following statement:

$class->checkFlag('FLAG_NAME', REDIRECT_TO_INDEX(true OR false));

If the user has the flag, it will return true. If not, false or if the redirect option is set to true, it will redirect to index.
It will return -1 if an error occured.
