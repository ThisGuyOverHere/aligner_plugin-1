import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";

const RowTarget = {
    drop(props, monitor) {
        const from = monitor.getItem();
        const types = {
            0: "source",
            1: "target"
        };
        const log = {
            type: types[from.type],
            from: from.order,
            to: props.children[from.type].props.order
        };
        ProjectActions.changeSegmentPosition(log);
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
        let rowClass = ['row'];
        const {connectDropTarget, isOver} = this.props;
        if (isOver){
            rowClass.push('dropHover');
        }
        return connectDropTarget(
            <div className="ui two column grid project-row">
                <div className={rowClass.join(' ')}>
                    <div className="column">
                        {this.props.children[0]}
                    </div>
                    <div className="column">
                        {this.props.children[1]}
                    </div>
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