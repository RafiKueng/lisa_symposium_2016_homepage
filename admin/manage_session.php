<?php
require "lib/header.php";


if (array_key_exists('sid', $_GET)) {
    $sid = $_GET['sid'];
    $sid_is_set = TRUE;

    $stmt = $db->prepare( "SELECT * FROM {$sessionsTable} WHERE id=:sid");
    $stmt->bindParam(':sid', $sid , PDO::PARAM_INT);
    $stmt->execute();
    $s = $stmt->fetch(PDO::FETCH_OBJ);

    if ( !in_array($USER->username, explode(";", $s->orgas)) &&  # an unauthorised user access existing stuff
         !in_array($USER->role, $special_power_roles) ) { # the user has super powers or asked nicely
        print "nice try, but you murdered me... die() [access denied]";
        die();
    }

    # parse the timeslots.. we'll use it anyways quite often
    # timeslots have format "2016-09-05 08:00/12:00; ..."
    if (!empty($s->timeslots)){
        $slots = explode(";",$s->timeslots);
        $startend_datetime = explode("/", $slots[0]); # for the moment, we only
                                                # support one timeslot per session

        $start_datetime = $startend_datetime[0];
        $start_date = explode(" ", $startend_datetime[0])[0];
        $end_datetime = $start_date . " " . $startend_datetime[1];

        $start_dt = new DateTime($start_datetime);
        $end_dt   = new DateTime($end_datetime);

        $dt_are_set = TRUE;
    }
    else {
        $dt_are_set = FALSE;
    }
}
else {$sid_is_set=FALSE;}



if (!empty($_POST)) {

    print "<h1>Processing POST</h1>\n";
    print_r($_POST);


    if (array_key_exists("action", $_POST)) {

        $action = $_POST["action"];
        unset($_POST["action"]);

        print_r($action);

        if ($action=="save") {

            $id = $_POST['id'];

            $atp = $_POST['acceptedType'];
            #$dat = $_POST['date'] . " " . $_POST['time'];
            #$dur = $_POST['duration'];
            $ppl = $_POST['posterPlace'];

            # check if strings valid
            if ($dt_are_set && $atp == PRESENTATION_TYPE_TALK) {
                echo "\ndtcheck >".$_POST['time']."<\n\n";
                if (empty($_POST['time'])) {
                    $dat = $start_dt->format($datetime_db_fstr);
                }
                if (empty($_POST['duration'])) {
                    $dur = 10; #default to 10minutes
                }
                try {
                    $dat = (
                        new DateTime(
                            $_POST['date'] . " " . $_POST['time']
                            )
                        )->format($datetime_db_fstr);
                    $tmp = new DateInterval('PT'.$_POST['duration'].'M');
                    $dur = $_POST['duration'];
                }
                catch (Exception $e) {
                    echo "\n\nunable to parse datetime and duration strings\nnot saving anything!";
                    die();
                }
            }
            else {
                $dat = NULL;
                $dur = NULL;
            }

            $stmtstr = "UPDATE {$tableName} SET
                acceptedType = :atp,
                presentationSlot = :dat,
                presentationDuration = :dur,
                posterPlace = :ppl
                WHERE id = :id;";
            $stmt = $db->prepare($stmtstr);
            $stmt->bindParam(':id', $id , PDO::PARAM_INT);
            $stmt->bindParam(':atp', $atp , PDO::PARAM_INT);
            $stmt->bindParam(':dat', $dat , PDO::PARAM_STR);
            $stmt->bindParam(':dur', $dur , PDO::PARAM_INT);
            $stmt->bindParam(':ppl', $ppl , PDO::PARAM_STR);

            $res = $stmt->execute();

            $target = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . ($sid ? "?sid=$sid" : "?_=_") . "#frmid" . $id; # ?_=_ part is needed to trigger actual reload
            #print "<script type='text/javascript'>window.location = '$target';</script>";
        }

        elseif ( $action=="get_json_data" ) {

            # apply access restrictions
            # if (array_key_exists('sid', $_POST)) { $sid = $_POST['sid']; }
            # else { die(); }

            $data = [];

            # set the intervals
            $start_i_dt = clone $start_dt;
            $start_i_dt->modify('-2 week');
            $end_i_dt = clone $end_dt;
            $end_i_dt->modify('+2 week');



            $stmtstr = "SELECT
                            id, title, firstname, lastname, affiliation,
                            presentationTitle, coauthors, abstract,
                            presentationSlot, presentationDuration
                        FROM {$tableName}
                        WHERE assignedSession=:sid
                            AND acceptedType=1
                            AND presentationSlot<>'';";
            $stmt = $db->prepare($stmtstr);
            $stmt->bindParam(':sid', $sid , PDO::PARAM_INT);
            $stmt->execute();
            $presentations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($presentations as $p) {
                $p['content'] = '';
                $p['start'] = '';
                $p['end'] = '';
            }

            $data['regions'] = [
                'before' => [
                    'id' => 'before',
                    'content' => 'XXX',
                    'start' => $start_i_dt->format('Y-m-d H:i'),
                    'end' => $start_dt->format('Y-m-d H:i'),
                    'type' => 'background',
                    'className' => 'negative'
                ],
                'after' => [
                    'id' => 'after',
                    'content' => 'XXX',
                    'start' => $end_dt->format('Y-m-d H:i'),
                    'end' => $end_i_dt->format('Y-m-d H:i'),
                    'type' => 'background',
                    'className' => 'negative'
                ],
                'conference' => [
                    'id' => 'conference',
                    'content' => 'The Conference',
                    'start' => $conferenceStartDay->format('Y-m-d H:i'),
                    'end' => $conferenceEndDay->format('Y-m-d H:i'),
                    'type' => 'background',
                    'className' => 'converence'
                ],
                'session' => [
                    'id' => 'session',
                    'content' => 'Your Session',
                    'start' => $start_dt->format('Y-m-d H:i'),
                    'end' => $end_dt->format('Y-m-d H:i'),
                    'type' => 'background',
                    'className' => 'session'
                ]
            ];

            $data['options'] = [
                'min' => $conferenceStartDay->format('Y-m-d H:i'),
                'max' => $conferenceEndDay->format('Y-m-d H:i')
            ];

            $data['presentations'] = $presentations;


            print_r($data);


            exit();

        }

        else {
            print "huch??";
            require "lib/footer.php";
            die();
        }

    }

    $db = null;
    require "lib/footer.php";
    die();
}

#
# DISPLAY MENU ONLY
#
if ( !$sid_is_set ) {

    $all_sessions = $db->query( "SELECT * FROM {$sessionsTable}")->fetchAll(PDO::FETCH_OBJ);
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];

?>
    <h1>Manage Your Sessions</h1>
    <h2>Select a session to manage:</h2>

    <ul class="pagemenu">
<?php
    foreach($all_sessions as $s) {
        if (in_array($_SESSION["username"], explode(';', $s->orgas)) ||
            in_array($USER->role, $special_power_roles)) {
            print "<li><a href='{$url}?sid={$s->id}'><code>[{$s->shortName}]</code> {$s->description}</a></li>\n";
        }
    }
    echo "    </ul>\n";

    require "lib/footer.php";
    exit();
}

#
# DISPLAY MANAGEMENT TOOLS
#
elseif ($sid_is_set) {

    print "<h1>Manage Your Session:</h1>";
    print "<p style='text-align:right;'><code>[{$s->shortName}]</code> {$s->description}</p>";

    $stmtstr = "SELECT
                    id, title, firstname, lastname, email, affiliation,
                    talkType, presentationTitle, coauthors, abstract, presentationCategories,
                    assignedSession, isPresentationAccepted, acceptedType,
                    presentationSlot, presentationDuration, posterPlace
                FROM {$tableName}
                WHERE assignedSession=:sid;";
    $stmt = $db->prepare($stmtstr);
    $stmt->bindParam(':sid', $sid , PDO::PARAM_INT);
    $stmt->execute();
    $presentations = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

    <h2>Overview / Timeline</h2>
<?php if ($dt_are_set) { ?>
    <p>
        Please be patient, I'll implement a nice little graphical overview in the next
        few days...
    </p>
    <div id="timeline_container"></div>
<?php } else { ?>
    <p>
        As soon as the session is set to a date, you will be able to arrange the talks on a timeline here.
    </p>
<?php } ?>

    <script type="text/javascript">
$(function(){
    var container = document.getElementById('timeline_container');

    var items = new vis.DataSet([
        {id: 'A', content: 'Period A', start: '2016-09-01 12:00', end: '2016-09-05', type: 'background'},
        {id: 'B', content: 'Period B', start: '2016-09-09', end: '2016-09-30', type: 'background', className: 'negative'},

        {id: 1, content: 'item 1', start: '2016-09-20'},
        {id: 2, content: 'item 2', start: '2016-09-14'},
        {id: 3, content: 'item 3', start: '2016-09-18'},
        {id: 4, content: 'item 4', start: '2016-09-16', end: '2016-09-19'},
        {id: 5, content: 'item 5', start: '2016-09-15'},
        {id: 6, content: 'item 6', start: '2016-09-17'}
    ]);

    // Configuration for the Timeline
    var options = {
        editable: true,
        min: new Date("2016-09-01"),
        max: new Date("2016-09-30"),
    };

    var groups = [
      {
        id: 1,
        content: 'Group 1'
        // Optional: a field 'className', 'style'
      }
      // more groups...
    ];

    var timeline = new vis.Timeline(container, items, options);

    timeline.on('rangechanged', function (properties) {
        console.log('rangechanged', properties);
    });
})
    </script>

    <h2>Presentations</h2>

<?php
    foreach($presentations as $p) {
        if ($p->talkType > 0) {$type = GET_PRES_STR($p->talkType); }
        else {$type = "none";}
        print "<!-- {$p->presentationSlot} / {$p->presentationDuration} -->";

        if ($dt_are_set) {
            if (isset($p->presentationSlot)) {
                $ptime = (new DateTime($p->presentationSlot))->format('H:i');
            }
            else {$ptime = "";}
            $pdate = $start_dt->format('Y-m-d'); # this is hardcoded to be on the day of the session atm
        }
        else {
            $ptime = "";
            $pdate = "";
        }
?>

        <form id="frmid<?=$p->id?>"
            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?sid=$sid"; ?>"
            method="post">
            <h3 class="presentation"><code>[<?=$p->id?>]</code> <?=$p->presentationTitle?></h3>
            <p class="summary">
                Type: <?=$type?> <br />
                By: <?=$p->title?> <?=$p->firstname?> <?=$p->lastname?> (<?=$p->email?>)<br />
                <i><?=$p->affiliation?></i><br />
                <small>Coauthors: <?=$p->coauthors?></small><br />
            </p>
            <p class="abstract">
                <code><?=nl2br($p->abstract) ?></code>
            </p>

            <div class="inputarea">
<?php
                    if (isset($p->isPresentationAccepted) && !$p->isPresentationAccepted) {
                        print "was rejeced <input type='submit' name='action' value='reconsider' >";
                    }
                    else {
                        $s0=''; $s1=''; $s2='';
                        if ($p->acceptedType == 1) {$s1=' selected';}
                        elseif ($p->acceptedType == 2) {$s2=' selected';}
                        else {$s0=' selected';}
?>
                <div class="half">
                    <label class="left">Accept as:
                        <select name="acceptedType" size="1">
                            <option value='-1'<?=$s0?>>-- not defined --</option>
                            <option value='0'<?=$s1?>>REJECT</option>
                            <option value='1'<?=$s1?>>talk</option>
                            <option value='2'<?=$s2?>>poster</option>
                        </select>
                    </label>
                    <br>
                    <input class="save" type="submit" name="action" value="save" >
                    <input class="warn" type="submit" name="action" value="REJECT" >
                </div>
                <div class="half">
                    <label class="left">
                        <small>[talk] starting time (24h):</small>
                        <input class="short" name="time" type="text" value="<?=$ptime?>"
                            placeholder="14:15" pattern="([01]?[0-9]|2[0-3]):[0-5][0-9]"
                            title="Enter a time in 24h HH:mm format, like 14:15"
                            <?= (!$dt_are_set ? "disabled" : "") ?>>
                    </label><br>
                    <label class="left">
                        <small>[talk] duration in minutes:</small>
                        <input class="short" name="duration" type="numeric" min=0 max=240
                            placeholder="25" value="<?=$p->presentationDuration?>"
                            <?= (!$dt_are_set ? "disabled" : "") ?>>
                    </label><br>
                    <label class="left">
                        <small>[poster] number / place:</small>
                        <input class="short" name="posterPlace" type="numeric" min=0 max=240
                            value="<?=$p->posterPlace?>">
                    </label>
                </div>
<?php               } ?>
            </div>
            <input type="hidden" name="date" value="<?=$pdate?>" >
            <input type="hidden" name="id" value="<?=$p->id?>" >
        </form>

<?php
    }

?>

<script>
    $(function(){
    })
</script>

<?php
    require "lib/footer.php";
    exit();
}
?>
