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