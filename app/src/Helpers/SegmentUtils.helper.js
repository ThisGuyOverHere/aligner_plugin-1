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

export const getSegmentByOrder = (order,type) => {
    return ProjectStore.job[type].find(e=>e.get('order') === order).toJS()
};


export const getSegmentIndexByOrder = (order,type) => {
    return ProjectStore.job[type].findIndex(i => i.get('order') === order);
};


export const getSegmentByIndex = (index,type) => {
    return ProjectStore.job[type].get(index).toJS();
};