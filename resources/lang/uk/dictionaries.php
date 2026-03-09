<?php

declare(strict_types=1);

return [
    'label' => 'Довідники',

    // Common translations for all program types
    'program_label' => 'Програма',
    'search_title' => 'Пошук програм',

    'medication_programs' => [
        'title' => 'Програми - Медикаменти',
        'prescription_medication' => 'Рецептурний лікарський засіб',

        // Program details block
        'funding_source' => 'Джерело фінансування',
        'mr_blank_type' => 'Тип рецептурного бланка',
        'care_plan_required' => "Обов'язковість використання плану лікування для EP",
        'employee_types_to_create_request' => 'Типи користувачів, яким дозволено виписувати EP',
        'speciality_types_allowed' => 'Перелік спеціальностей лікарів СМД та ПМД, яким дозволено виписувати EP/Призначення ПЛ',
        'skip_treatment_period' => 'Можливість виписувати EP на такий самий МНН протягом курсу лікування',
        'request_max_period_day' => 'Максимальна тривалість курсу лікування на який може бути виписаний EP за програмою',
        'skip_request_employee_declaration_verify' => 'Можливість виписувати EP незалежно від наявності укладеної декларації з пацієнтом',
        'skip_request_legal_entity_declaration_verify' => 'Можливість виписувати EP незалежно від наявності укладеної декларації в закладі, де виписується EP',
        'multi_medication_dispense_allowed' => 'Можливість часткового погашення EP',
        'request_notification_disabled' => 'Сповіщення пацієнта при операціях з рецептом вимкнено',
        'patient_categories_allowed' => 'Категорії пацієнтів, яким дозволено створення призначення ПЛ'
    ],

    'service_programs' => [
        'title' => 'Програми - Послуги',

        // Program details block
        'medical_guarantees' => 'Програма медичних гарантій',
        'care_plan_required' => "Обов'язковість використання плану лікування для ЕН"
    ],

    'drug_list' => [
        'title' => 'Лікарські засоби',

        // Search
        'search_title' => 'Пошук ліків',
        'search_placeholder' => 'Пошук',

        // Additional filters
        'inn_name' => 'Міжнародна непатентована назва ЛЗ',
        'atc_code' => 'Код анатоміко-терапевтично-хімічної класифікації',
        'dosage_form' => 'Форма випуску ЛЗ',
        'prescription_form_type_filter' => 'Тип рецептурного бланка',
        'atc_placeholder' => 'Код',
        'type_placeholder' => 'Тип',

        // Program selection
        'program_label' => 'Програма',
        'program_label_required' => 'Програма',
        'program_placeholder' => 'Оберіть програму',

        // Radio options
        'prescription_medication' => 'Рецептурний лікарський засіб',

        // Details panel
        'details_title' => 'Рецептурний лікарський засіб – деталі програми',
        'funding_source' => 'Джерело фінансування',
        'prescription_form_type' => 'Тип рецептурного бланка',
        'treatment_plan_required' => 'Обов\'язковість використання плану лікування для ЕР',
        'allowed_user_types' => 'Типи користувачів, яким дозволено виписувати ЕР',
        'allowed_specialties' => 'Перелік спеціальностей лікарів СМД та ПМД, яким дозволено виписувати ЕР/Призначення ПЛ',
        'same_inn_course' => 'Можливість виписувати ЕР на такий самий МНН протягом курсу лікування',
        'max_course_duration' => 'Максимальна тривалість курсу лікування на який може бути виписаний ЕР за програмою',
        'no_declaration_required_patient' => 'Можливість виписувати ЕР незалежно від наявності укладеної декларації з пацієнтом',
        'no_declaration_required_facility' => 'Можливість виписувати ЕР незалежно від наявності укладеної декларації в закладі, де виписується ЕР',
        'partial_redemption' => 'Можливість часткового погашення ЕР',
        'patient_notifications_off' => 'Сповіщення пацієнта при операціях з рецептом вимкнено',
        'allowed_patient_categories' => 'Категорії пацієнтів, яким дозволено створення призначення ПЛ',
    ],

    'service_catalog' => [
        'title' => 'Каталог послуг',
        'search_services' => 'Пошук послуг',
        'search_placeholder' => 'Киснева терапія',
        'service_category' => 'Категорія послуг',
        'service_active' => 'Послуга активна',
        'service_group_active' => 'Група послуг активна',
        'allowed_for_en' => 'Дозволяється використання у ЕН',

        'categories' => [
            'nervous_system' => 'Процедури на нервовій системі',
        ],
    ],

    'loinc_observation_dictionary' => 'Довідник спостережень LOINC',
    'icf_dictionary_condition_patient' => 'Довідник станів пацієнта МКФ',
];
