@php
    $dashboardUrl = legalEntity() ? route('dashboard', [legalEntity()]) : url('/dashboard');
    $patientUrl = route($prepersonId !== null ? 'prepersons.patient-data' : 'persons.patient-data', [legalEntity(), $prepersonId !== null ? 'preperson' : 'person' => $prepersonId ?? $personId]);
    $requestsUrl = route($prepersonId !== null ? 'prepersons.prescription-requests' : 'persons.prescription-requests', [legalEntity(), $prepersonId !== null ? 'preperson' : 'person' => $prepersonId ?? $personId]);
@endphp

<x-layouts.patient
    :personId="$personId"
    :prepersonId="$prepersonId"
    :patientFullName="$patientFullName"
    :hideNavigation="true"
    title="{{ __('medication-requests.zaiavky_na_retsiepty') }}"
>
    <x-slot name="headerActions"></x-slot>
    <div class="shift-content pl-3.5 mt-8 max-w-6xl">
        <fieldset class="fieldset">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                <div class="flex flex-col">
                    <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-6">{{ __('medication-requests.zahalna_informatsiya_pro_zayavku') }}</div>

                    <div class="form-row-1">
                        <div class="form-group group">
                            <input value="" type="text" class="input peer" disabled />
                            <label class="label">{{ __('medication-requests.prohrama') }}</label>
                        </div>
                    </div>

                    <div class="form-row-1 mt-4">
                        <div class="form-group group">
                            <input value="" type="text" class="input peer" disabled />
                            <label class="label">ID {{ __('medication-requests.zayavky') }}</label>
                        </div>
                    </div>

                    <div class="form-row-1 mt-4">
                        <div class="form-group group">
                            <input value="" type="text" class="input peer" disabled />
                            <label class="label">{{ __('medication-requests.status') }}</label>
                        </div>
                    </div>
                </div>

                <div>
                    <fieldset class="border border-gray-400 dark:border-gray-600 rounded-2xl p-5 h-full">
                        <legend class="text-lg font-bold text-gray-800 dark:text-gray-200 px-3">{{ __('medication-requests.detali_prohramy') }}</legend>
                        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                            {{ __('medication-requests.dzherelo_finansuvannya') }}:<br>
                            {{ __('medication-requests.typ_retsepturnoho_blanka') }}:<br>
                            {{ __('medication-requests.obov_yazkovist_vykorystannya_planu_likuv') }}:<br>
                            {{ __('medication-requests.typy_korystuvachiv_yakym_dozvoleno_vypys') }}:<br>
                            {{ __('medication-requests.perelik_spetsialnostey_likariv_smd_ta_pm') }}:<br>
                            {{ __('medication-requests.mozhlyvist_vypysuvaty_er_na_takyy_samyy') }}:<br>
                            {{ __('medication-requests.maksymalna_tryvalist_kursu_likuvannya_na') }}:<br>
                            {{ __('medication-requests.mozhlyvist_vypysuvaty_er_nezalezhno_vid') }}:<br>
                            {{ __('medication-requests.mozhlyvist_vypysuvaty_er_nezalezhno_vid') }}:<br>
                            {{ __('medication-requests.mozhlyvist_chastkovoho_pohashennya_er') }}:<br>
                            {{ __('medication-requests.spovishchennya_patsiyenta_pry_operatsiya') }}:<br>
                            {{ __('medication-requests.katehoriyi_patsiyentiv_yakym_dozvoleno_s') }}:
                        </div>
                    </fieldset>
                </div>
            </div>

            <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-10 mb-6">{{ __('medication-requests.informatsiya_shchodo_vypysanoho_lz') }}</div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.nazva_lz') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.skladovi_likarskoho_zasobu_mnn') }})</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.forma_vypusku_lz') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.obsyah_pervynnoyi_upakovky_vypysanoho_lz') }}</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.razova_doza') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.dobova_doza') }}</label>
                </div>
            </div>
            <div class="form-row-1 mb-4 mt-2">
                <div class="form-group group">
                    <label class="block mb-1 text-[10.5px] text-gray-400">{{ __('medication-requests.syhnatura') }}*</label>
                    <textarea class="textarea resize-none" disabled rows="3"></textarea>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.kilkist_vypysanoho_lz') }}</label>
                </div>
            </div>

            <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-10 mb-6">{{ __('medication-requests.stroky_likuvannya_ta_otrymannya_lz') }}</div>
            <div class="form-row-2">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input value="" type="text" class="peer input pl-10 appearance-none" disabled />
                    <label class="wrapped-label">{{ __('medication-requests.data_stvorennya_zayavky') }}</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input value="" type="text" class="peer input pl-10 appearance-none" disabled />
                    <label class="wrapped-label">{{ __('medication-requests.data_pochatku_kursu_likuvannya_vypysanym') }}</label>
                </div>
                <div class="form-group datepicker-wrapper relative w-full">
                    <input value="" type="text" class="peer input pl-10 appearance-none" disabled />
                    <label class="wrapped-label">{{ __('medication-requests.data_zavershennya_kursu_likuvannya_vypys') }}</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input value="" type="text" class="peer input pl-10 appearance-none" disabled />
                    <label class="wrapped-label">{{ __('medication-requests.data_pershoho_dnya_koly_mozhlyvo_otrymat') }}</label>
                </div>
                <div class="form-group datepicker-wrapper relative w-full">
                    <input value="" type="text" class="peer input pl-10 appearance-none" disabled />
                    <label class="wrapped-label">{{ __('medication-requests.data_ostannoho_dnya_koly_mozhlyvo_otryma') }}</label>
                </div>
            </div>

            <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-10 mb-6">{{ __('medication-requests.informatsiya_pro_shusoz_v_yakomu_bulo_vy') }}</div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.nazva_shusoz') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.kod_yedrpou_abo_rnokpp_u_razi_fop') }}</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.publichna_nazva') }}</label>
                </div>
            </div>

            <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-10 mb-6">{{ __('medication-requests.informatsiya_pro_likarya_ta_patsiyenta') }}</div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.pib_likarya') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.kontaktni_dani_likarya') }}</label>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.pib_patsiyenta') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">{{ __('medication-requests.kilkist_povnykh_rokiv_patsiyenta') }}</label>
                </div>
            </div>

            <div class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-10 mb-6">{{ __('medication-requests.pov_yazani_medychni_zapysy') }}</div>
            <div class="form-row-2 mb-10">
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">Id {{ __('medication-requests.planu_likuvannya_na_osnovi_yakoho_stvore') }}</label>
                </div>
                <div class="form-group group">
                    <input value="" type="text" class="input peer" disabled />
                    <label class="label">Id {{ __('medication-requests.vzayemodiyi_v_skladi_yakoyi_stvoreno_er') }}</label>
                </div>
            </div>

        </fieldset>

        <div class="flex items-center gap-4 mt-8 pb-10">
            <a href="{{ $requestsUrl }}" class="button-minor px-6 py-2.5">{{ __('medication-requests.nazad') }}</a>
            <button type="button" class="button-primary-outline-red px-6 py-2.5">{{ __('medication-requests.vidminyty_zayavku') }}</button>
            <button type="button" class="button-primary px-6 py-2.5">{{ __('medication-requests.pidpysaty_zayavku') }}</button>
        </div>
    </div>
</x-layouts.patient>
