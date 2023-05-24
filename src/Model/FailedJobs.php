<?php namespace KlbV2\Core\Model;

use Phalcon\Mvc\Model;

/**
 * Class FailedJobs
 *
 * SQL: CREATE TABLE `failed_jobs` (
 * `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 * `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
 * `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
 * `exception` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 * `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
 *
 * @package KlbV2\Core\Model
 */
class FailedJobs extends Model
{
    use SelectOption;

    /**
     * @return array
     */
    protected static function selectOptionUsing()
    {
        return [
            'queue',
            'queue',
            'queue'
        ];
    }

    public function initialize()
    {
        $this->setSource( 'failed_jobs' );
    }

    public function getSource()
    {
        return "failed_jobs";
    }
}
