/**
 *
 * @param {Number} x current order position of element
 * @param {Number} y next current order position of element
 * @return {number} AVG Order
 */
export const avgOrder = (x, y) => {
    return x + (y - x) / 2;
};

/**
 *
 * @param title
 * @returns {string} with a centerd ellipsis
 */
export const titleEllipsisCenter = (title) => {
    if(title.length > 30){
        return title.substr(0, 12) + '[...]' + title.substr(title.length-12, title.length);
    }
    return title;
};