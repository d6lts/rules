<?php

/**
 * @file
 * Contains \Drupal\rules\Plugin\RulesExpression\ConditionExpression.
 */

namespace Drupal\rules\Plugin\RulesExpression;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Engine\RulesConditionBase;
use Drupal\rules\Engine\RulesExpressionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an executable condition expression.
 *
 * This plugin is used to wrap condition plugins and is responsible to setup all
 * the context necessary, instantiate the condition plugin and to execute it.
 *
 * @RulesExpression(
 *   id = "rules_condition",
 *   label = @Translation("An executable condition.")
 * )
 */
class ConditionExpression extends RulesConditionBase implements RulesExpressionInterface, ContainerFactoryPluginInterface {

  /**
   * The condition manager used to instantiate the condition plugin.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * Constructs a ConditionExpression object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   *   Contains the following entries:
   *   - condition_id: The condition plugin ID.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConditionManager $conditionManager) {
    // Per default the result of this expression is not negated.
    $configuration += ['negate' => FALSE];
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->conditionManager = $conditionManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->evaluate();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $condition = $this->conditionManager->createInstance($this->configuration['condition_id'], [
      'negate' => $this->configuration['negate'],
    ]);
    // We have to forward the context values from our configuration to the
    // condition plugin.
    $contexts = $condition->getContextDefinitions();
    foreach ($contexts as $name => $context) {
      $condition->setContext($name, $this->getContext($name));
    }
    $result = $condition->evaluate();
    // @todo Now that the condition has been executed it can provide additional
    // context which we will have to pass back to any parent expression.
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // @todo A condition expression has no summary. Or should we forward this to
    //   the condition plugin?
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinitions() {
    if (!isset($this->contextDefinitions)) {
      // Pass up the context definitions from the condition plugin.
      $condition = $this->conditionManager->createInstance($this->configuration['condition_id']);
      $this->contextDefinitions = $condition->getContextDefinitions();
    }
    return $this->contextDefinitions;
  }

}
