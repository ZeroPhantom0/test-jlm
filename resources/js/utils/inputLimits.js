// resources/js/utils/inputLimits.js
//
// Frontend mirror of App\Support\InputRules (the backend is the real
// enforcement — these just stop users typing past the limit and give
// instant feedback). Keep the two files in sync.

export const LIMITS = {
    NAME: 100,
    EMAIL: 254,
    RESERVATION_EMAIL: 120,
    PHONE: 20,
    PASSWORD_MIN: 8,
    PASSWORD_MAX: 64,
    TITLE: 150,
    SUBJECT: 150,
    CATEGORY: 100,
    SHORT_TEXT: 255,
    ADDRESS: 255,
    ROOM_LABEL: 50,
    ROOM_NUMBER: 20,
    REFERENCE_NO: 50,
    TIME_TEXT: 20,
    TIME_WINDOW: 50,
    NOTES: 1000,
    LONG_TEXT: 2000,
    MESSAGE_BODY: 5000,
    CONTENT: 5000,
    COMPANIONS: 2000,
    SEARCH: 100,
    OTP: 6,

    MONEY_MAX: 1000000,
    RENT_MAX: 100000,
    MAX_FLOORS: 50,
    MAX_BEDS: 50,
    MAX_OCCUPANTS: 20,

    // File sizes in bytes
    AVATAR_BYTES: 5 * 1024 * 1024,
    IMAGE_BYTES: 10 * 1024 * 1024,
    PANORAMA_BYTES: 20 * 1024 * 1024,
    ATTACHMENT_BYTES: 10 * 1024 * 1024,
    MAX_ATTACHMENTS: 5,
};

export default LIMITS;
