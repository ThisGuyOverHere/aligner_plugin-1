import React, {Component} from 'react';
import {ItemTypes} from '../../../../Constants/Draggable.constants';
import {DragSource} from 'react-dnd';
import PropTypes from "prop-types";

const ItemSource = {
    beginDrag(props) {
        return props;
    },
    canDrag(props,monitor){
        return (!props.rowChecked && props.value);
    }
};

function collect(connect, monitor) {
    return {
        connectDragSource: connect.dragSource(),
        isDragging: monitor.isDragging(),
        canDrag: monitor.canDrag()
    }
}

class SegmentComponent extends Component {
    constructor(props) {
        super(props);

        this.state = {};
    }

    static getDerivedStateFromProps(props, state) {

        return null;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    render() {
        const {connectDragSource, isDragging, canDrag} = this.props;
        let cursorDrag = 'not-allowed';
        if(isDragging){
            cursorDrag ='move';
        }else if(canDrag && !isDragging){
            cursorDrag = 'move'
        }
        return connectDragSource(
            <div style={{
                opacity: isDragging ? 0.5 : 1,
                fontSize: 16,
                cursor: cursorDrag
            }}>
                <p>{this.props.value}</p>
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {
    }

    componentWillUnmount() {
    }
}

SegmentComponent.propTypes = {
    type: PropTypes.number.isRequired,
    rowChecked: PropTypes.bool.isRequired,
    order: PropTypes.number.isRequired,
    value: PropTypes.string
};

export default DragSource(ItemTypes.ITEM, ItemSource, collect)(SegmentComponent);