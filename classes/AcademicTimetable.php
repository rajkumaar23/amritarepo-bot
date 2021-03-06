<?php
/**
 * Copyright (c) 2020 | RAJKUMAR (http://rajkumaar.co.in)
 */

use GuzzleHttp\Client;
use voku\helper\HtmlDomParser;

/**
 * Copyright (c) 2020 | RAJKUMAR (http://rajkumaar.co.in)
 */
class AcademicTimetable
{

    public static function handle($bot, $from, $msg)
    {
        switch ($msg) {
            case '/actimetable' :
                $reply = self::getYears();
                break;
            default:
                switch (sizeof(explode("_", $msg))) {
                    case 2:
                        $reply = self::getCourses($msg);
                        break;
                    case 3:
                        $reply = self::getSemesters($msg);
                        break;
                    case 4:
                        $reply = self::getBranches($msg);
                        break;
                    case 5:
                        $reply = self::getSections($msg);
                        break;
                    case 6:
                        $bot->sendMessage($from, "Please wait for a moment, while I search for the requested document :) \n`In case I dont respond, it means that the file has not yet been uploaded.`", "markdown");
                        try {
                            $bot->sendDocument($from, self::getTimetableURL($msg));
                            $bot->sendMessage($from, "There you go! Check out your academic timetable !!");
                        } catch (\TelegramBot\Api\HttpException $exception) {
                            Logger::error($exception->getMessage() . "\n" . $exception->getTraceAsString());
                        }
                        return "";
                    default:
                        global $reply;
                        break;
                }
                break;
        }
        return $reply;
    }

    public static function getYears()
    {
        $current_year = date("Y");
        $year = "";
        for ($i = 4, $j = 3; $i >= 0; $i--, $j--) {
            $year .= "\n\n(" . (($current_year - $i) . "-" . substr((string)((int)$current_year - $j), 2, 4)) . ")  -  /act_" . (($current_year - $i) . "" . substr((string)((int)$current_year - $j), 2, 4));
        }
        $year .= "\n\nPlease choose `current` academic year from the below options : ";
        return $year;
    }

    public static function getCourses($year)
    {
        $client = new Client();
        $response = $client->request('GET', 'https://intranet.cb.amrita.edu/TimeTable');
        $dom = HtmlDomParser::str_get_html($response->getBody()->__toString());
        $courseList = $dom->getElementById('drop_1');
        $courseItems = $courseList->getElementsByTagName('option');
        $courses = "Please choose your course from the options below :";
        $i = 1;
        $format = "%-10s";
        foreach ($courseItems as $item) {
            if (trim($item->getAttribute('value')) != "") {
                $courses .= "\n\n" . $i . ") " . sprintf($format, trim($item->getAttribute('value'))) . " -   " . $year . "_" . trim($item->getAttribute('value'));
                $i++;
            }
        }
        return $courses;
    }

    public static function getSemesters($prev)
    {
        $sems = "Okay now! You're progressing nice! Choose your semester : ";
        for ($i = 1; $i <= 10; ++$i) {
            $sems .= "\n\n Semester " . $i . "   -  " . $prev . "_" . $i;
        }
        return $sems;
    }

    public static function getSections($prev)
    {
        $data = explode('_', $prev);
        $msg = $data[0] . '_' . $data[1] . '_' . $data[2] . '_' . $data[3];
        $branch = $data[4];
        $sections = "Okay! Final question xD ! Which section do you belong to ?";
        for ($i = 'A'; $i <= 'F'; ++$i) {
            $sections .= "\n\n Section " . $i . "   -  " . $msg . "_" . $i . '_' . $branch;
        }
        return $sections;
    }

    private static function getBranches($msg)
    {
        $course = explode("_", $msg)[2];
        $response = "Okay cool! Please be patient. Just one more! Choose your branch : ";
        $client = new Client();
        $res = $client->request('GET', 'https://intranet.cb.amrita.edu/TimeTable/funcTimeTable.php?func=drop_1&drop_var=' . $course);
        $dom = HtmlDomParser::str_get_html($res->getBody()->__toString());
        $branchList = $dom->getElementById('drop_2');
        $branches = $branchList->getElementsByTagName('option');
        $i = 1;
        foreach ($branches as $branch) {
            if (trim($branch->getAttribute('value')) != "") {
                $response .= "\n\n" . trim($branch->getAttribute('value')) . "   -  " . $msg . "_" . trim($branch->getAttribute('value'));
            }
        }
        return $response;
    }

    public static function getTimetableURL($msg)
    {
        $url = 'https://intranet.cb.amrita.edu/TimeTable/PDF';
        $data = explode("_", $msg);
        $url .= "/" . substr($data[1], 0, 4) . "_" . substr($data[1], 4, 6);
        $url .= "/" . $data[2];
        $url .= "/" . $data[5];
        $url .= "/" . $data[2] . $data[5] . $data[4] . $data[3] . ".pdf";
        return $url;
    }
}
