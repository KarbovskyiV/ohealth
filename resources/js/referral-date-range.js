// Only referral filters opt into ISO values and strict manual input handling.
export function bindReferralDateRange(el) {
    let invalidInput = null;
    const emitRange = (from, to = "") => {
        el.dispatchEvent(
            new CustomEvent("date-range-change", {
                bubbles: true,
                detail: { from, to },
            }),
        );
    };

    const onChange = (selectedDates, dateStr, instance) => {
        invalidInput = null;
        emitRange(
            selectedDates[0]
                ? instance.formatDate(selectedDates[0], "Y-m-d")
                : "",
            selectedDates[1]
                ? instance.formatDate(selectedDates[1], "Y-m-d")
                : "",
        );
    };

    const isValidInput = (instance) => {
        if (el.value === "") return true;
        const dates = el.value.split(instance.config.locale.rangeSeparator);
        return (
            dates.length <= 2 &&
            dates.every((value) => {
                if (!/^\d{2}\.\d{2}\.\d{4}$/.test(value)) return false;
                const date = instance.parseDate(value, "d.m.Y");
                return date && instance.formatDate(date, "d.m.Y") === value;
            })
        );
    };

    el.addEventListener("input", () => {
        const instance = el._flatpickr;
        if (!instance) return;
        if (!isValidInput(instance)) {
            // Preserve invalid text so server validation shows a field error.
            invalidInput = el.value;
            emitRange(el.value);
            return;
        }
        invalidInput = null;
        instance.setDate(el.value, false, "d.m.Y");
        onChange(instance.selectedDates, el.value, instance);
    });

    const preventInvalidParsing = (event) => {
        const instance = el._flatpickr;
        if (instance && !isValidInput(instance)) {
            // Flatpickr otherwise silently rolls impossible dates into the next month.
            event.stopImmediatePropagation();
            emitRange(el.value);
        }
    };
    el.addEventListener("blur", preventInvalidParsing, true);
    el.addEventListener(
        "keydown",
        (event) => {
            if (event.key === "Enter") preventInvalidParsing(event);
        },
        true,
    );

    const onClose = (selectedDates, dateStr, instance) => {
        const invalidValue = invalidInput;
        const dates = [...selectedDates];
        // Flatpickr's outside-click handler parses text and clears single-date ranges
        // after onClose. Restore the referral value once that handler has finished.
        queueMicrotask(() => {
            if (!instance.config) return;
            if (invalidValue !== null) {
                instance.clear(false);
                el.value = invalidValue;
            } else if (
                dates.length === 1 &&
                instance.selectedDates.length === 0
            ) {
                instance.setDate(dates, false);
            }
        });
    };

    return { onChange, onClose };
}
