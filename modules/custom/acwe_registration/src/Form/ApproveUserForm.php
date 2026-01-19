<?php

namespace Drupal\acwe_registration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a confirmation form for approving a user registration.
 */
class ApproveUserForm extends ConfirmFormBase {

  /**
   * The user being approved.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acwe_registration_approve_user';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Approve registration for %name?', [
      '%name' => $this->user->getDisplayName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Email: @email<br>This will activate the account and send a welcome email.', [
      '@email' => $this->user->getEmail(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('acwe_registration.approval_dashboard');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Approve');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?User $user = NULL) {
    $this->user = $user;
    
    // Check if user was loaded
    if (!$this->user) {
      $this->messenger()->addError($this->t('User not found.'));
      return $form;
    }
    
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Activate the user account
    $this->user->activate();
    $this->user->save();

    // Add user to their section group
    $this->addUserToSectionGroup();

    // Add user to the Members group (persistent membership)
    $this->addUserToMembersGroup();

    // Log the approval
    \Drupal::logger('acwe_registration')->info('User @name approved by @approver', [
      '@name' => $this->user->getAccountName(),
      '@approver' => \Drupal::currentUser()->getAccountName(),
    ]);

    // Send welcome email
    _user_mail_notify('status_activated', $this->user);

    $this->messenger()->addStatus($this->t('User %name has been approved and notified.', [
      '%name' => $this->user->getDisplayName(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Add the approved user to their selected section group.
   */
  protected function addUserToSectionGroup() {
    // Check if user has a primary section selected
    if (!$this->user->hasField('field_primary_section') || $this->user->get('field_primary_section')->isEmpty()) {
      \Drupal::logger('acwe_registration')->warning('User @name approved without a primary section', [
        '@name' => $this->user->getAccountName(),
      ]);
      return;
    }

    $section_key = $this->user->get('field_primary_section')->value;
    
    // Map section keys to group IDs
    $section_map = [
      'conductor' => 1,
      'flutes' => 2,
      'doublereeds' => 3,
      'highclarinets' => 4,
      'lowclarinets' => 5,
      'saxophones' => 6,
      'horns' => 7,
      'trumpets' => 8,
      'trombones' => 9,
      'lowbrass' => 10,
      'percussion' => 11,
    ];
    
    if (!isset($section_map[$section_key])) {
      \Drupal::logger('acwe_registration')->error('Invalid section key: @key for user @name', [
        '@key' => $section_key,
        '@name' => $this->user->getAccountName(),
      ]);
      return;
    }

    $group_id = $section_map[$section_key];
    
    // Load the group and add the user as a member
    $group_storage = \Drupal::entityTypeManager()->getStorage('group');
    $group = $group_storage->load($group_id);
    
    if (!$group) {
      \Drupal::logger('acwe_registration')->error('Section group @id not found for user @name', [
        '@id' => $group_id,
        '@name' => $this->user->getAccountName(),
      ]);
      return;
    }

    // Add user to group as a member (without specifying roles - default member role will be assigned)
    $group->addMember($this->user);
    
    \Drupal::logger('acwe_registration')->info('Added user @name to group @group', [
      '@name' => $this->user->getAccountName(),
      '@group' => $group->label(),
    ]);
  }

  /**
   * Add the approved user to the Members group for general member content.
   */
  protected function addUserToMembersGroup() {
    // The Members group ID (Group ID 13 - contains general member content)
    $members_group_id = 13;
    
    // Load the Members group and add the user as a member
    $group_storage = \Drupal::entityTypeManager()->getStorage('group');
    $members_group = $group_storage->load($members_group_id);
    
    if (!$members_group) {
      \Drupal::logger('acwe_registration')->error('Members group @id not found for user @name', [
        '@id' => $members_group_id,
        '@name' => $this->user->getAccountName(),
      ]);
      return;
    }

    // Add user to Members group (persistent - stays even when on_break)
    $members_group->addMember($this->user);
    
    \Drupal::logger('acwe_registration')->info('Added user @name to Members group', [
      '@name' => $this->user->getAccountName(),
    ]);
  }

}
