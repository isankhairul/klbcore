<?php namespace Klb\Core\Model;

use Phalcon\Mvc\Model;

/**
 * Class MailTemplate
 *
 * @package Klb\Core\Model
 *          SQL:REATE TABLE `mail_template` (
 * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 * `name` varchar(100) NOT NULL,
 * `code` varchar(100) DEFAULT 'NULL',
 * `type` varchar(100) NOT NULL,
 * `active` tinyint(1) NOT NULL DEFAULT 0,
 * `recipients` varchar(500) DEFAULT NULL,
 * `sender` varchar(100) NOT NULL,
 * `bcc` varchar(300) DEFAULT NULL,
 * `subject` varchar(100) NOT NULL,
 * `body` text NOT NULL,
 * `variables` text DEFAULT NULL,
 * `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 * `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
 * PRIMARY KEY (`id`),
 * UNIQUE KEY `code` (`code`),
 * KEY `name` (`name`),
 * KEY `type` (`type`),
 * KEY `active` (`active`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1
 */
class MailTemplate extends Model
{
    /**
     *
     */
    public function beforeSave()
    {
        if ( empty( $this->code ) && !empty( $this->name ) ) {
            $this->code = slug( $this->name );
        }

        if ( !empty( $this->variables ) && is_scalar( $this->variables ) ) {
            $this->variables = serialize( $this->variables );
        }
    }

    /**
     *
     */
    public function afterFetch()
    {

        if ( is_string( $this->variables ) ) {
            $this->variables = @unserialize( $this->variables );
        }
    }
}
