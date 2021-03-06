<?php

namespace Drupal\embargoes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EmbargoesLogController.
 */
class EmbargoesLogController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs an embargo log controller.
   *
   * @var \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(Connection $database) {
    $this->connection = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }

  /**
   * Creates markup for a log entry.
   *
   * @return array
   *   A renderable array of markup representing a log.
   */
  public function showRenderedLog() {
    $result = $this->connection
      ->select('embargoes_log', 'el')
      ->fields('el')
      ->orderBy('el.time', 'DESC')
      ->execute();

    $formatted_log = [];
    foreach ($result as $record) {
      $formatted_time = date('c', $record->time);
      $node_title = $this->entityTypeManager()->getStorage('node')->load($record->node)->get('title')->value;
      $user = $this->entityTypeManager()->getStorage('user')->load($record->uid);
      $username = $user ? $user->getUsername() : $this->t('Missing User');
      if ($record->action == "deleted") {
        $embargo_formatted = ['#markup' => "<span style='text-decoration:line-through;'>{$record->embargo}</span>"];
      }
      else {
        $embargo_formatted = $this->getLinkGenerator()->generate($record->embargo, Url::fromRoute('entity.embargoes_embargo_entity.edit_form', [
          'embargoes_embargo_entity' => $record->embargo,
        ]));
      }

      $row = [
        'id' => $record->id,
        'embargo' => $embargo_formatted,
        'time' => $formatted_time,
        'action' => ucfirst($record->action),
        'node' => $this->getLinkGenerator()->generate($node_title, Url::fromRoute('entity.node.canonical', [
          'node' => $record->node,
        ])),
        'user' => $this->getLinkGenerator()->generate($username, Url::fromRoute('entity.user.canonical', [
          'user' => $record->uid,
        ])),
      ];
      array_push($formatted_log, $row);
    }

    $log = [
      '#type' => 'table',
      '#header' => [
        $this->t('Event ID'),
        $this->t('Embargo ID'),
        $this->t('Time'),
        $this->t('Action'),
        $this->t('Embargoed Node'),
        $this->t('User Responsible'),
      ],
      '#rows' => $formatted_log,
    ];

    return [
      'log' => $log,
    ];
  }

}
