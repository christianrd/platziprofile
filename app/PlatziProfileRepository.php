<?php

namespace App;

class PlatziProfileRepository
{
    const PROFILE_URI = '//platzi.com/@';

    protected $client;

    public function __construct($client) {
        $this->client =  $client;
    }

    public function find($username) {

        /**
         * Make request with the request() method
         * @return Crawler object
         *
         */
        $crawler = $this->client->request('GET', self::PROFILE_URI.$username);

        /**
         * Ensure is valid link for request
         */
        $statusCode = $this->client->getResponse()->getStatus();
        if ($statusCode == 404) {
            return false;
        }

        /**
         * Achievements DOM nodes
         * (only approved)
         *
         */
        $achievementNodes = $crawler->filter('section.AchievementList > article.Achievement')->reduce(function($node, $i) {
            $nodeClasses = $node->attr('class');
            $pos = strpos($nodeClasses, 'notApproved');
            if ($pos) {
                // To remove the node return false
                return false;
            }
        });

        /**
         * Get DOM nodes that contains the badges data
         * @param $achievementNodes
         * @return array with all DOM nodes with badge data
         *
         */
        function getAchievementNodesData($achievementNodes) {
            $achievementNodesData = array();

            $achievementNodes->each(function($node) use (&$achievementNodesData) {
                $name = $node->filter('h3');
                $url = $node->filter('img');
                $achievementNodesData[] = compact('name', 'url');
            });

            return $achievementNodesData;
        }

        $achievementNodesData = getAchievementNodesData($achievementNodes);

        /**
         * Create collections for Achievement Nodes Data
         * There are two collections: 'careers' & 'courses'
         * @param $achievementNodes array nodes data
         * @return array with collections
         *
         */
        function sortAchieveNodesData($achievementNodesData){
            $achievementCollections = array();
            $achievementCollections['careers'] = array();
            $achievementCollections['courses'] = array();

            foreach ($achievementNodesData as $nodesData) {
                $imageNode = $nodesData['url'];
                $figureNode = $imageNode->parents('figure');
                $figureClasses = $figureNode->attr('class');

                if (strpos($figureClasses, 'is-course')) {
                    $achievementCollections['courses'][] = $nodesData;
                } else if (strpos($figureClasses, 'is-career')) {
                    $achievementCollections['careers'][] = $nodesData;
                }
            }

            return $achievementCollections;
        }

        $achievementCollections = sortAchieveNodesData( $achievementNodesData );

        /**
         * Extract data from DOM nodes into an array
         * @param $dataNodes array with Nodes
         * @return array with data
         *
         */
        function nodesToArray($dataNodes = array()) {
            $data = array();

            foreach($dataNodes as $key => $node) {
                if ($node->count()) {
                    $tagName = $node->nodeName();
                    switch ($tagName) {
                        case "a":
                            $data[$key] = $node->link()->getUri();
                            break;
                        case "img":
                            $data[$key] = $node->attr('src');
                            break;
                        case "p":
                            $data[$key] = $node->text();
                            break;
                        case "h3":
                            $data[$key] = $node->text();
                            break;
                    }
                }
            }

            return $data;
        }

        /**
         * Extract data from Achievement Nodes Data
         * @param $achievementCollections
         * @return array with data
         *
         */
        function getAchievementsData($achievementCollections) {
            $achievementsData = array();
            foreach($achievementCollections as $collection => $achievementNodesData ) {
                foreach($achievementNodesData as $nodesData) {
                    $achievementData = nodesToArray($nodesData);
                    $achievementsData[$collection][] = $achievementData;
                }
            }
            return $achievementsData;
        }

        $achievementsData = getAchievementsData($achievementCollections);

        /**
         * Profile DOM node
         *
         */
        $profileNode = $crawler->filter('div.ProfilePersonal');

        /**
         * Get DOM array nodes that contains the profile data
         * @param $profileNode main profile DOM node
         * @return array with all DOM nodes with profile data
         *
         */
        function getProfileDataNodes($profileNode) {

            $avatar = $profileNode->filter('img#avatar');
            $badge = $profileNode->filter('img.ProfilePersonal-badge');
            $name = $profileNode->filter('p.ProfilePersonal-name');
            $country = $profileNode->filter('p.ProfilePersonal-country');
            $url = $profileNode->filter('p.ProfilePersonal-url > a');
            $twitter = $profileNode->selectLink('twitter');
            $facebook = $profileNode->selectLink('facebook');

            return compact('avatar', 'badge', 'name', 'country', 'url', 'twitter', 'facebook');
        }

        $profileNodesData = getProfileDataNodes($profileNode);

        $profileData = nodesToArray($profileNodesData);

        $platziProfile = array(
            'user' => $profileData,
            'badges' => $achievementsData
        );

        return json_encode($platziProfile);
    }
}
