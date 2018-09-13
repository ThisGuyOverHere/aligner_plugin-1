import ProjectStore from '../Stores/Project.store';

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
 * @param {Number} order the order of segment you want to get
 * @param {String} type of segment you want to get
 * @return {Object} Segment
 */
export const getSegmentByOrder = (order,type) => {
    return ProjectStore.job[type].find(e=>e.get('order') === order).toJS()
};

/**
 *
 * @param {Number} order the order of segment you want to get
 * @param {String} type of segment you want to get
 * @return {number} index the index of segment you search
 */
export const getSegmentIndexByOrder = (order,type) => {
    return ProjectStore.job[type].findIndex(i => i.get('order') === order);
};

/**
 *
 * @param {Number} index the index of segment you want to get
 * @param {String} type of segment you want to get
 * @return {Object} segment
 */
export const getSegmentByIndex = (index,type) => {
    return ProjectStore.job[type].get(index).toJS();
};