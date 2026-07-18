<?php

return [

    'SUBSCRIPTION_STATUS' => [
        'PENDING' => 'pending',
        'ACTIVE' => 'active',
        'INACTIVE' => 'inactive',
    ],
    'USER_PERMISSION_ALLOW' => [
        //
    ],

       'MODULES' => [
        [
            'module_name' => 'Rating',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Comments',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Users',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Movies',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Shows',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Seasons',
            'is_custom_permission' => 0,
        ],
        [
            'module_name' => 'Episodes',
            'is_custom_permission' => 0,
        ],

        // System / owner pages. Custom permission (single "access" toggle,
        // no view/add/edit/delete matrix). Default hidden — not granted to
        // the admin role by the seeder; a super-admin grants access here.
        // Slugs → permission names: settings_access, seo_access,
        // system_info_access, pages_access.
        [
            'module_name' => 'Settings',
            'is_custom_permission' => 1,
            'more_permission' => ['access'],
        ],
        [
            'module_name' => 'SEO',
            'is_custom_permission' => 1,
            'more_permission' => ['access'],
        ],
        [
            'module_name' => 'System Info',
            'is_custom_permission' => 1,
            'more_permission' => ['access'],
        ],
        [
            'module_name' => 'Pages',
            'is_custom_permission' => 1,
            'more_permission' => ['access'],
        ],

        // NOTE: the Referrals back office deliberately has NO permission
        // entry — the whole surface (overview + settings) is gated
        // role:super-admin at the route level, so a grantable permission
        // here would only mislead the role matrix.

    ],
];
