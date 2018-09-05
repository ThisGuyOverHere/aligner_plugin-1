/**
 *
 * @param text
 * @returns {string} with a centerd ellipsis
 */
export const textEllipsisCenter = (text) => {
    if(text.length > 30){
        return text.substr(0, 12) + '[...]' + text.substr(text.length-12, text.length);
    }
    return text;
};

/**
 *
 * @param name
 * @param surname
 * @returns {string} the initials of user name
 */
export const getUserInitials = (name, surname) => {
    return name.split(" ").map((n)=>n[0]).join("") + surname.split(" ").map((n)=>n[0]).join("") ;
};