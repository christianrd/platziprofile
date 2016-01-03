<?php

require __DIR__ . '/vendor/autoload.php';

// Create and configure Slim app
$app = new \Slim\App;

// Define app routes
$app->get('/', function($request, $response, $args) {
    $homeUrl = (string)($request->getUri()->withPath('')->withQuery('')->withFragment(''));
    return <<<EOT
    API de Consumo para tu perfil de Platzi y tus badges. Ejemplo: <a href="{$homeUrl}LeonidasEsteban">{$homeUrl}LeonidasEsteban</a>
EOT;
});
$app->get('/{name}', function ($request, $response, $args) {

    /**
     * Create a Goutte Client instance
     *
     */
    $client = new Goutte\Client();

    /**
     * Make request with the request() method
     * @return Crawler object
     *
     */
    $crawler = $client->request('GET', '//platzi.com/profile/'.$args['name']);

    /**
     * Ensure is valid link for request
     */
    $statusCode = $client->getResponse()->getStatus();
    if ($statusCode == 404) {
        $body = json_encode(['detail' => 'Not found']);
        $response->write($body);
        $response = $response->withHeader('Content-Type', 'application/json')->withStatus(404)->withHeader('Access-Control-Allow-Origin', '*');
        return $response;
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

        return compact('avatar', 'badge', 'name', 'country', 'url', 'twitter', 'facebok');
    }

    $profileNodesData = getProfileDataNodes($profileNode);

    $profileData = nodesToArray($profileNodesData);

    $platziProfile = array(
        'profile' => $profileData,
        'badges' => $achievementsData
    );

    $body = json_encode($platziProfile);
    $response->write($body);
    $response = $response->withHeader('Content-Type', 'application/json')->withHeader('Access-Control-Allow-Origin', '*');

    return $response;

});

// Run app
$app->run();
