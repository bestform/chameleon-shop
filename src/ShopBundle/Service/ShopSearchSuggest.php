<?php

/*
 * This file is part of the Chameleon System (https://www.chameleonsystem.com).
 *
 * (c) ESONO AG (https://www.esono.de)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ChameleonSystem\ShopBundle\Service;

use ChameleonSystem\ShopBundle\Interfaces\ShopSearchSuggestInterface;
use Doctrine\DBAL\Connection;

class ShopSearchSuggest implements ShopSearchSuggestInterface
{
    /**
     * @var Connection
     */
    private $databaseConnection;

    /**
     * @param Connection $databaseConnection
     */
    public function __construct(Connection $databaseConnection)
    {
        $this->databaseConnection = $databaseConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchSuggestions($searchTerm)
    {
        if ('' === trim($searchTerm)) {
            return array();
        }
        // simple filter to avoid XSS attacks - only works with German/English
        $searchTerm = preg_replace("/[^a-zA-ZäÄöÖüÜß0-9-\s]/", '', $searchTerm);

        $searchTerm1 = $this->databaseConnection->quote('%'.$searchTerm.'%');
        $searchTerm2 = $this->databaseConnection->quote($searchTerm.'%');
        $aQueries[] = "SELECT `name` AS name FROM `shop_article` WHERE `name` LIKE $searchTerm1 ";
        $aQueries[] = "SELECT `articlenumber` AS name FROM `shop_article` WHERE `articlenumber` LIKE $searchTerm2";

        $aSuggestions = array();
        $finalQuery = '';
        $isFirst = true;
        foreach ($aQueries as $sQuery) {
            if (!$isFirst) {
                $finalQuery .= ' UNION ';
            }
            $finalQuery .= '('.$sQuery.')';
            $isFirst = false;
        }
        $results = $this->databaseConnection->fetchAll($finalQuery);
        foreach ($results as $result) {
            $aSuggestions[] = $result['name'];
        }
        $aSuggestions = array_unique($aSuggestions);

        $suggestions = array();
        $suggestions[] = $searchTerm;
        $suggestions = array_merge($suggestions, $aSuggestions);

        return $suggestions;
    }
}
