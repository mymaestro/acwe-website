<?php

namespace Drupal\acwe_registration\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

/**
 * Provides a confirmation form for denying a user registration.
 */
class DenyUserForm extends ConfirmFormBase {

  /**
   * The user being denied.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acwe_registration_deny_user';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Deny registration for %name?', [
      '%name' => $this->user->getDisplayName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Email: @email<br>This will permanently delete this account.', [
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
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    $this->user = $user;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Log the denial
    \Drupal::logger('acwe_registration')->info('User @name denied by @approver', [
      '@name' => $this->user->getAccountName(),
      '@approver' => \Drupal::currentUser()->getAccountName(),
    ]);

    // Delete the user account
    $this->user->delete();

    $this->messenger()->addStatus($this->t('Registration for %name has been denied and the account deleted.', [
      '%name' => $this->user->getDisplayName(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
