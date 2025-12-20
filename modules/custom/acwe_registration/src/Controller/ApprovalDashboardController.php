<?php

namespace Drupal\acwe_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;

/**
 * Controller for the approval dashboard.
 */
class ApprovalDashboardController extends ControllerBase {

  /**
   * Display the pending registrations dashboard.
   */
  public function dashboard() {
    // Query for blocked (unapproved) users
    $query = \Drupal::entityQuery('user')
      ->condition('status', 0)
      ->condition('uid', 0, '>')
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);
    
    $uids = $query->execute();
    
    if (empty($uids)) {
      return [
        '#markup' => '<div class="messages messages--status">' . 
          $this->t('No pending registrations at this time.') . 
          '</div>',
      ];
    }

    $users = User::loadMultiple($uids);
    
    $rows = [];
    foreach ($users as $user) {
      $created = $user->getCreatedTime();
      $time_ago = \Drupal::service('date.formatter')->formatInterval(
        \Drupal::time()->getRequestTime() - $created
      );
      
      $rows[] = [
        'name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
        'created' => $time_ago . ' ago',
        'operations' => [
          'data' => [
            '#type' => 'operations',
            '#links' => [
              'approve' => [
                'title' => $this->t('Approve'),
                'url' => \Drupal\Core\Url::fromRoute('acwe_registration.approve_user', [
                  'user' => $user->id(),
                ]),
              ],
              'deny' => [
                'title' => $this->t('Deny'),
                'url' => \Drupal\Core\Url::fromRoute('acwe_registration.deny_user', [
                  'user' => $user->id(),
                ]),
              ],
              'view' => [
                'title' => $this->t('View'),
                'url' => \Drupal\Core\Url::fromRoute('entity.user.canonical', [
                  'user' => $user->id(),
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $build['pending_count'] = [
      '#markup' => '<div class="pending-count"><h2>' . 
        $this->formatPlural(count($rows), 
          '1 pending registration', 
          '@count pending registrations'
        ) . '</h2></div>',
    ];

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Registered'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No pending registrations.'),
    ];

    return $build;
  }

}
