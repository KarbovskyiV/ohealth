const assert = require("node:assert/strict");
const { test } = require("node:test");
const flatpickr = require("flatpickr");

async function rangeInput() {
    const { bindReferralDateRange } =
        await import("../../resources/js/referral-date-range.js");
    const input = new EventTarget();
    input.value = "";
    let range;
    input.addEventListener("date-range-change", (event) => {
        range = event.detail;
    });
    const { onChange, onClose } = bindReferralDateRange(input);
    const instance = (input._flatpickr = {
        config: { locale: { rangeSeparator: " — " } },
        selectedDates: [],
        formatDate: flatpickr.formatDate,
        parseDate: flatpickr.parseDate,
        clear() {
            this.selectedDates = [];
            input.value = "";
        },
        setDate(value) {
            this.selectedDates = Array.isArray(value)
                ? value
                : value
                  ? value
                        .split(" — ")
                        .map((date) => this.parseDate(date, "d.m.Y"))
                  : [];
            input.value = this.selectedDates
                .map((date) => this.formatDate(date, "d.m.Y"))
                .join(" — ");
        },
    });
    return { input, instance, onChange, onClose, range: () => range };
}

for (const field of ["filterStartedAtRange", "filterEndedAtRange"]) {
    test(`${field}: outside click preserves invalid text and a single-date range`, async () => {
        const { input, instance, onChange, onClose, range } =
            await rangeInput();
        input.value = "31.02.2026";
        input.dispatchEvent(new Event("input"));
        onClose([], "", instance);
        instance.clear();
        await Promise.resolve();
        assert.equal(input.value, "31.02.2026");
        assert.deepEqual(range(), { from: "31.02.2026", to: "" });
        const date = new Date(2026, 9, 1);
        onChange([date], "01.10.2026", instance);
        onClose([date], "01.10.2026", instance);
        instance.clear();
        await Promise.resolve();
        assert.equal(input.value, "01.10.2026");
        assert.deepEqual(range(), { from: "2026-10-01", to: "" });
    });

    test(`${field}: selectedDates sends local ISO dates for one date, a range and clear`, async () => {
        const { instance, onChange, range } = await rangeInput();
        const start = new Date(2026, 9, 1);
        const end = new Date(2026, 9, 29);
        onChange([start], "01.10.2026", instance);
        assert.deepEqual(range(), { from: "2026-10-01", to: "" });
        onChange([start, end], "01.10.2026 — 29.10.2026", instance);
        assert.deepEqual(range(), { from: "2026-10-01", to: "2026-10-29" });
        onChange([], "", instance);
        assert.deepEqual(range(), { from: "", to: "" });
    });

    test(`${field}: valid manual dates and clearing synchronize selectedDates`, async () => {
        const { input, instance, range } = await rangeInput();
        for (const [value, expected] of [
            ["01.10.2026", { from: "2026-10-01", to: "" }],
            [
                "01.10.2026 — 29.10.2026",
                { from: "2026-10-01", to: "2026-10-29" },
            ],
            ["", { from: "", to: "" }],
        ]) {
            input.value = value;
            input.dispatchEvent(new Event("input"));
            assert.deepEqual(range(), expected);
            assert.equal(
                instance.selectedDates.length,
                value ? value.split(" — ").length : 0,
            );
        }
    });

    test(`${field}: invalid manual input survives blur and Enter for server validation`, async () => {
        for (const value of [
            "not-a-date",
            "31.02.2026",
            "01.10.2026 - 29.10.2026",
            "01.10.2026 — invalid",
        ]) {
            const { input, range } = await rangeInput();
            let parsed = false;
            input.addEventListener("blur", () => {
                parsed = true;
            });
            input.addEventListener("keydown", () => {
                parsed = true;
            });
            input.value = value;
            input.dispatchEvent(new Event("input"));
            input.dispatchEvent(new Event("blur"));
            const enter = new Event("keydown");
            enter.key = "Enter";
            input.dispatchEvent(enter);
            assert.equal(parsed, false);
            assert.equal(input.value, value);
            assert.deepEqual(range(), { from: value, to: "" });
        }
    });
}
