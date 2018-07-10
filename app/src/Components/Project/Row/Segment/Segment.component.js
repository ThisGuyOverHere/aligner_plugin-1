import React, {Component} from 'react';
import {ItemTypes} from '../../../../Constants/Draggable.constants';
import {DragSource} from 'react-dnd';

const ItemSource = {
    beginDrag(props) {
        /*console.log(props);*/
        return props;
    }
};

function collect(connect, monitor) {
    return {
        connectDragSource: connect.dragSource(),
        isDragging: monitor.isDragging()
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
        const {connectDragSource, isDragging} = this.props;
        return connectDragSource(
            <div style={{
                opacity: isDragging ? 0.5 : 1,
                fontSize: 16,
                cursor: 'move'
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

export default DragSource(ItemTypes.ITEM, ItemSource, collect)(SegmentComponent);