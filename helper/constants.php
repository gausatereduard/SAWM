<?php
declare(strict_types=1);

/**
 * Single source of truth for role and permission names.
 * The database schema, the seed data and the authorization checks all use these.
 */

const ROLE_ADMINISTRATOR = 'administrator';
const ROLE_MANAGER = 'manager';

const PERM_USERS_VIEW = 'users.view';
const PERM_USERS_UPDATE = 'users.update';
const PERM_USERS_DELETE = 'users.delete';
const PERM_REPORTS_VIEW = 'reports.view';
const PERM_PROFILE_UPDATE = 'profile.update';
