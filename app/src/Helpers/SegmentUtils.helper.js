/**
 *
 * @param {Number} x current order position of element
 * @param {Number} y next current order position of element
 * @return {number} AVG Order
 */
export const avgOrder = (x, y) => {
    return x + (y - x) / 2;
};

