<?php

declare(strict_types=1);

return [
    'new' => 'Нове обладнання',
    'name_medical_product' => 'Назва медичного обладнання',
    'name_type' => 'Тип назви',
    'type_medical_device' => 'Тип медичного обладнання',
    'serial_number' => 'Серійний №',
    'id' => 'eHealth ID',
    'recorder' => 'Працівник, що вносить дані',
    'recorded_by' => 'Працівник, що вніс дані',
    'additional_data' => 'Додаткові дані',
    'inventory_number' => 'Інвентарний №',
    'manufacturer' => 'Виробник',
    'manufacture_date' => 'Дата виробництва',
    'expiration_date' => 'Термін експлуатації надавачем',
    'model_number' => '№ моделі',
    'lot_number' => '№ закупівлі',
    'label' => 'Обладнання',
    'search' => 'Пошук обладнання',
    'name_or_inventory_number' => 'Назва або інвентарний №',
    'inserted_at' => 'Дата внесення даних',
    'parent_id' => "Пов'язане обладнання",
    'add_name' => 'Додати назву',
    'update_status' => 'Змінити статус',
    'update_availability_status' => 'Змінити доступність',
    'reason_for_status_change' => 'Причина зміни статусу',
    'update_equipment_status' => 'Оновити статус обладнання ',
    'update_equipment_availability' => 'Оновити доступність обладнання',
    'child_equipment_warning' => 'Це обладнання є батьківським. З ним зареєстровано таке обладнання:',

    'status' => [
        'active' => 'Активний',
        'inactive' => 'Неактивний',
        'entered_in_error' => 'Внесено помилково',
        'draft' => 'Чернетка'
    ],

    'availability_status' => [
        'label' => 'Доступність',
        'available' => 'Доступний',
        'damaged' => 'Пошкоджений',
        'destroyed' => 'Не підлягає відновленню',
        'lost' => 'Відсутній'
    ],

    'type' => [
        'patient_reported' => 'Вказана пацієнтом',
        'registered' => 'Зареєстрована',
        'user_friendly' => 'Користувацька'
    ],

    'success' => [
        'created' => 'Обладнання успішно створено',
        'draft_created' => 'Чернетку обладнання успішно створено',
        'status_updated' => 'Статус обладнання успішно оновлено',
        'availability_status_updated' => 'Доступність обладнання успішно оновлено',
    ],

    'policy' => [
        'create' => 'У вас немає дозволу на створення обладнання',
        'edit' => 'У вас немає дозволу на редагування обладнання',
        'update' => 'У вас немає дозволу на оновлення статусу обладнання',
        'update_availability_status' => 'У вас немає дозволу на оновлення доступності обладнання',
        'sync' => 'У вас немає дозволу на синхронізацію обладнань'
    ],

    'errors' => [
        'sync_failed' => 'Виникла помилка. Оновіть список місць надання послуг та працівників і спробуйте ще раз'
    ],

    'api_errors' => [
        'availability_status_already_set' => 'Обладнання вже має статус доступності :availability_status',
        'equipment_write_not_allowed' => 'Для вашої ролі відсутній доступ до цього ресурсу. Необхідний доступ на створення запису про обладнання',
        'division_not_allowed' => 'Користувачеві заборонено створювати записи про медичні вироби для цього підрозділу',
        'parent_not_found' => 'Батьківське обладнання не знайдено',
        'parent_not_active' => 'Батьківське обладнання, на яке посилаються, не активне',
        'parent_of_another_legal_entity' => 'Батьківське обладнання, на яке посилаються, належить іншій юридичній особі',
        'device_definition_type_mismatch' => 'Модель медичного виробу, на який посилаються, має бути того самого типу, що й обладнання',
        'serial_number_required' => 'Для цього типу обладнання необхідний серійний номер',
        'status_must_be_active' => 'Статус має бути активним',
        'availability_status_must_be_available' => 'Статус доступності має бути доступним',
        'manufacture_date_in_future' => 'Дата виробництва обладнання має бути раніше або дорівнювати поточній даті',
        'one_property_value_allowed' => 'Зі списку дозволено лише один ключ: [value_integer, value_decimal, value_boolean, value_string], але присутні такі: :present',
        'type_mismatch' => 'Невідповідність типу. Очікувався :expected_type, але отримано :actual_type',
        'dictionary_value_must_be_string' => 'Для значень зі словника дозволено використовувати лише value_string',
        'inventory_number_not_unique' => 'Інвентарний номер повинен бути унікальним',
        'name_required' => 'Потрібно вказати принаймні одну назву',
        'name_type_duplicated' => 'Тип назви медичного виробу не можна дублювати',
        'equipment_read_not_allowed' => 'Для вашої ролі відсутній доступ до цього ресурсу. Необхідний доступ на отримання інформації про обладнання',
        'final_status_transition' => 'Недійсний перехід. Статус обладнання кінцевий',
        'status_change_prohibited' => 'Заборонено змінювати статус обладнання з поточним статусом доступності = :availability_status',
        'error_reason_required' => 'Необхідно вказати причину помилки',
        'equipment_must_be_active' => 'Недійсний статус. Обладнання має бути активним'
    ],

    'validation' => [
        'availability_status_unchanged' => 'Новий статус доступності повинен відрізнятися від поточного.',
        'not_belongs_to_division' => 'Обладнання не належить вибраному місцю надання послуг.',
        'invalid_status_transition' => 'Статус "Неактивний" можна встановити лише для активного обладнання.'
    ]
];
