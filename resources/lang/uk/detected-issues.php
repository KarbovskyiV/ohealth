<?php

declare(strict_types=1);

return [
    'label' => 'Виявлені проблеми медичних виробів',
    'new' => 'Нова виявлена проблема медичного виробу',
    'add' => 'Додати виявлену проблему медичного виробу',
    'search' => 'Пошук проблем асоційованих медичних виробів',
    'id' => 'ID проблеми',

    'device' => 'Медичний виріб',
    'device_name' => 'Назва виробу',
    'device_id' => 'ID виробу',
    'status' => 'Статус запису',
    'entered_in_error' => 'Внесена помилково',

    'identified_at' => 'Дата та час виявлення',
    'identified_at_short' => 'Дата та час виявлення проблеми',

    'type' => 'Тип виявленої проблеми',
    'detail' => 'Опис проблеми',
    'text_for_input' => 'Текст для введення',

    'implicated_device' => 'Медичний виріб, який спричинив проблему',
    'based_on' => 'Попередня виявлена проблема',

    'legal_entity' => 'СГУСОЗ',
    'record_created_at' => 'Дата створення запису',

    'policy' => [
        'create' => 'У вас немає дозволу на створення виявленої проблеми медичного виробу.'
    ],

    // Number of the record every :attribute of a validation message carries
    'position' => 'виявлена проблема №:position',

    // Field names for :attribute in validation messages
    'attributes' => [
        'subjectId' => 'Медичний виріб',
        'status' => 'Статус запису',
        'identifiedDate' => 'Дата та час виявлення',
        'identifiedTime' => 'Дата та час виявлення',
        'code' => 'Тип виявленої проблеми',
        'detail' => 'Опис проблеми',
        'implicatedId' => 'Медичний виріб, який спричинив проблему',
        'basedOnId' => 'Попередня виявлена проблема',
        'reportOriginCode' => 'Посилання на джерело'
    ],

    'validation' => [
        'device_not_found' => 'Обраний медичний виріб не знайдено у пацієнта або поточній взаємодії.',
        'based_on_not_found' => 'Попередню виявлену проблему не знайдено.',
        'based_on_self' => 'Виявлена проблема не може посилатися сама на себе.',
        'based_on_subject_mismatch' => 'Попередня виявлена проблема має стосуватися того самого медичного виробу.'
    ]
];
