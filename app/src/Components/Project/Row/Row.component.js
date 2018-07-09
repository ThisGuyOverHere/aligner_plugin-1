import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';

const RowTarget = {
    drop(props, monitor) {
        const from = monitor.getItem();
        const labels = {
            0: "Source",
            1: "Target"
        };
        const posFrom = +from.position + 1,
            posTo = +props.children[from.type].props.position + 1;
        console.log("Sposto il " + labels[from.type] + " dalla riga " + posFrom + " alla riga " + posTo)
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver()
    }
}


class RowComponent extends Component {
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
        const {connectDropTarget } = this.props;
        return connectDropTarget(
            <div className='row'>
                <div className="col-sm-6">
                    {this.props.children[0]}
                </div>
                <div className="col-sm-6">
                    {this.props.children[1]}
                </div>

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

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);