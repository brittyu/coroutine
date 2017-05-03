<?php

include "Scheduler.php";
include "Task.php";

function newTask(Generator $coroutine)
{
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($coroutine) {
        $task->setSendValue($scheduler->newTask($coroutine));
        $scheduler->scheduler($task);
    });
}

function waitForRead($socket)
{
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($socket) {
        $scheduler->waitForRead($socket, $task);
    });
}

function waitForWrite($socket)
{
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($socket) {
        $scheduler->waitForWrite($socket, $task);
    });
}

function childTask()
{
    $taskId = (yield getTaskId());
    while (true) {
        echo "Child task $taskId still alive!\n";
        yield;
    }
}

function task()
{
    $taskId = (yield getTaskId());
    $childTaskId = (yield newTask(childTask()));

    for ($i = 1; $i <= 6; ++$i) {
        echo "Parent task $taskId iteration $i.\n";
        yield;

        if ($i == 3) {
            yield killTask($childTaskId);
        }
    }
}

function killTask($taskId)
{
    return new SystemCall(function(Task $task, Scheduler $scheduler) use ($taskId) {
        $task->setSendValue($scheduler->killTask($taskId));
        $scheduler->scheduler($task);
    });
}

function getTaskId()
{
    return new SystemCall(function(Task $task, Scheduler $scheduler) {
        $task->setSendValue($task->getTaskId());
        $scheduler->scheduler($task);
    });
}

// function task($max)
// {
//     $taskId = (yield getTaskId());
//     $childTaskId = (yield newTask(childTask()));
//     for ($i = 1; $i <= $max; ++$i) {
//         echo "This is task $taskId iteration $i.\n";
//         yield;
//     }
// }

$scheduler = new Scheduler;

//$scheduler->newTask(task(10));
$scheduler->newTask(task());

$scheduler->run();
