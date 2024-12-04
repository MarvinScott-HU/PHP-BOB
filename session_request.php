<?php

$json_file = file_get_contents("json/attributes.json");

$json_file_date = json_decode($json_file, true);

function getSessionByCode($data, $sessionCode)
{
    foreach ($data['sessions'] as $session) {
        if ($session['sessionCode'] === $sessionCode) {
            return $session;
        }
    }

    return null;
}

function getSession($data, $sessionCode): bool|string
{
    if ($sessionCode) {
        $result = getSessionByCode($data, $sessionCode);

        if ($result) {
            return json_encode($result);
        } else {
            return json_encode(['error' => "Session with code $sessionCode not found."]);
        }

    } else {
        return json_encode(['error' => 'No sessionCode provided.']);
    }
}

function updateValuesForParty($data, $sessionCode, $partyId, $newValues): bool
{
    // the first element of the array is null for some reason so start at 1
    // TODO: what the f***
    $index = 1;
    foreach ($data['sessions'] as &$session) {
        if ($session['sessionCode'] === $sessionCode) {
            foreach ($session['measures'] as &$measure) {
                foreach ($measure['valuePerParty'] as &$valuePerParty) {
                    if ($valuePerParty['partyId'] == $partyId) {
                        $valuePerParty['value'] = $newValues[$index];
                        $index++;
                    }
                }
            }
            save_data_to_file($data);
            return true;
        }
    }
    return false;
}

function updateSessionByCode($data, $sessionCode, $description, $lastChange, $budget, $peopleNeedingHelp, $measures, $parties): bool
{
    foreach ($data['sessions'] as &$session) {
        if ($session['sessionCode'] === $sessionCode) {
            $session['description'] = $description;

            $session['lastChange'] = $lastChange;

            $session['budget'] = $budget;
            $session['peopleNeedingHelp'] = $peopleNeedingHelp;

            $session['measures'] = $measures;
            $session['parties'] = $parties;

            save_data_to_file($data);

            return true;
        }
    }
    return false;
}

function createSession($data, $sessionCode, $description, $lastChange, $budget, $peopleNeedingHelp, $measures, $parties): void
{
    $new_session = [
        "sessionCode" => $sessionCode,
        "description" => $description,

        "lastChange" => time(),

        "budget" => $budget,
        "peopleNeedingHelp" => $peopleNeedingHelp,

        "measures" => $measures,
        "parties" => $parties
    ];

    $data['sessions'][] = $new_session;

    save_data_to_file($data);
}

function save_data_to_file($data): void
{
    file_put_contents("json/attributes.json", json_encode($data));
}

$sessionCode = $_GET['sessionCode'] ?? null;
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');

    $data = json_decode($json, true);

    if ($data === null) {
        echo json_encode(["error" => "Invalid JSON"]);
        exit;
    }

    $sessionCode = $data['sessionCode'] ?? null;
    $description = $data['description'] ?? null;

    $lastChange = $data['lastChange'] ?? null;

    $budget = $data['budget'] ?? null;
    $peopleNeedingHelp = $data['peopleNeedingHelp'] ?? null;

    $measures = $data['measures'] ?? [];
    $parties = $data['parties'] ?? [];

    createSession($json_file_date, $sessionCode, $description, $lastChange, $budget, $peopleNeedingHelp, $measures, $parties);

    $response = [
        "sessionCode" => $sessionCode,
        "description" => $description,

        "lastChange" => $lastChange,

        "budget" => $budget,
        "peopleNeedingHelp" => $peopleNeedingHelp,

        "measuresCount" => $measures,
        "partiesCount" => $parties,
    ];

    echo json_encode($response);
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET["party"])) {
        $json = file_get_contents('php://input');

        $data = json_decode($json, true);

        if ($data === null) {
            echo json_encode(["error" => "Invalid JSON"]);
            exit;
        }

        $sessionCode = $data['sessionCode'] ?? null;
        $description = $data['description'] ?? null;

        $lastChange = $data['lastChange'] ?? null;

        $budget = $data['budget'] ?? null;
        $peopleNeedingHelp = $data['peopleNeedingHelp'] ?? null;

        $measures = $data['measures'] ?? [];
        $parties = $data['parties'] ?? [];

        $newValues[] = null;

        foreach ($measures as &$measure) {
            foreach ($measure['valuePerParty'] as &$valuePerParty) {
                if ($valuePerParty['partyId'] == $_GET['party']) {
                    $newValues[] += $valuePerParty['value'];
                }
            }
        }

        updateValuesForParty($json_file_date, $sessionCode, $_GET['party'], $newValues);

        $response = [
            "sessionCode" => $sessionCode,
            "description" => $description,

            "lastChange" => $lastChange,

            "budget" => $budget,
            "peopleNeedingHelp" => $peopleNeedingHelp,

            "measuresCount" => $measures,
            "partiesCount" => $parties,
        ];

        echo json_encode($response);
    }
    else {
        $json = file_get_contents('php://input');

        $data = json_decode($json, true);

        if ($data === null) {
            echo json_encode(["error" => "Invalid JSON"]);
            exit;
        }

        $sessionCode = $data['sessionCode'] ?? null;
        $description = $data['description'] ?? null;

        $lastChange = $data['lastChange'] ?? null;

        $budget = $data['budget'] ?? null;
        $peopleNeedingHelp = $data['peopleNeedingHelp'] ?? null;

        $measures = $data['measures'] ?? [];
        $parties = $data['parties'] ?? [];

        updateSessionByCode($json_file_date, $sessionCode, $description, $lastChange, $budget, $peopleNeedingHelp, $measures, $parties);

        $response = [
            "sessionCode" => $sessionCode,
            "lastChange" => $lastChange,
            "budget" => $budget,
            "peopleNeedingHelp" => $peopleNeedingHelp,
            "measuresCount" => $measures,
            "partiesCount" => $parties,
        ];

        echo json_encode($response);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo getSession($json_file_date, $sessionCode);
}
