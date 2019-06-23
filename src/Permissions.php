<?php

namespace RA;

abstract class Permissions
{
	const Spam = -2;
	const Banned = -1;
	const Unregistered = 0;
	const Registered = 1;
	const JrDeveloper = 2;
	const Developer = 3;
	const Admin = 4;
	const Root = 5;
}
