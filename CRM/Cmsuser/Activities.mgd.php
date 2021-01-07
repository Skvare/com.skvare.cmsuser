<?php
return [
  [
    'name' => 'cmsuser.activity.create',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'activity_type',
      'name' => 'User Account Creation',
      'description' => 'CMS user account created.',
      'is_active' => 1,
    ]
  ],
  [
    'name' => 'cmsuser.activity.password',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'activity_type',
      'name' => 'User Account Password Reset',
      'description' => 'Sent a password reset email.',
      'is_active' => 1,
    ]
  ],
  [
    'name' => 'cmsuser.activity.failed',
    'entity' => 'OptionValue',
    'params' => [
      'option_group_id' => 'activity_status',
      'name' => 'Failed',
      'description' => 'Activity failed.',
      'is_active' => 1,
    ]
  ],
];
