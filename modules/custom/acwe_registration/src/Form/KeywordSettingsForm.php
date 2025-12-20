<?php

namespace Drupal\acwe_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure rehearsal keyword settings.
 */
class KeywordSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['acwe_registration.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'acwe_registration_keyword_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_keyword = \Drupal::state()->get('acwe.rehearsal_keyword', '');
    $last_updated = \Drupal::state()->get('acwe.rehearsal_keyword.updated', 0);
    
    $form['keyword'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current Rehearsal Keyword'),
      '#description' => $this->t('Case-insensitive. Keep it simple (one word is best).'),
      '#default_value' => $current_keyword,
      '#required' => TRUE,
    ];

    if ($last_updated > 0) {
      $form['last_updated'] = [
        '#type' => 'item',
        '#title' => $this->t('Last Updated'),
        '#markup' => \Drupal::service('date.formatter')->format($last_updated, 'long'),
      ];
    }

    $form['tips'] = [
      '#type' => 'details',
      '#title' => $this->t('Tips'),
      '#open' => FALSE,
    ];

    $form['tips']['info'] = [
      '#markup' => '<ul>
        <li>' . $this->t('Change monthly or when needed') . '</li>
        <li>' . $this->t('Announce at every rehearsal') . '</li>
        <li>' . $this->t('Keep it simple (one word is best)') . '</li>
        <li>' . $this->t('Examples: "Mozart", "November", "Austin"') . '</li>
      </ul>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $keyword = trim($form_state->getValue('keyword'));
    
    \Drupal::state()->set('acwe.rehearsal_keyword', $keyword);
    \Drupal::state()->set('acwe.rehearsal_keyword.updated', \Drupal::time()->getRequestTime());
    \Drupal::state()->set('acwe.rehearsal_keyword.updated_by', \Drupal::currentUser()->id());

    \Drupal::logger('acwe_registration')->info('Rehearsal keyword updated by @user', [
      '@user' => \Drupal::currentUser()->getAccountName(),
    ]);

    parent::submitForm($form, $form_state);
  }

}
