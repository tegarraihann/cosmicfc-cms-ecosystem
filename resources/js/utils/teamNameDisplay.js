export function teamNameSizeClass(name) {
    const length = Array.from(String(name || "").trim()).length;

    return {
        "is-medium": length > 11 && length <= 19,
        "is-long": length > 19 && length <= 29,
        "is-extra-long": length > 29,
    };
}
