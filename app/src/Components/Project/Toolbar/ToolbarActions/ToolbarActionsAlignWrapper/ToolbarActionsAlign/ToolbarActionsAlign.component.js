import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../../Actions/Project.actions";
import {getSegmentByIndex, getSegmentIndexByOrder} from "../../../../../../Helpers/SegmentUtils.helper";
import Hotkeys from "react-hot-keys";

class ToolbarActionsAlignComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = true;
        if (this.props.selection.source.count === 1
            && this.props.selection.target.count === 1) {

            //check elements not are in same line
            if (getSegmentIndexByOrder(this.props.selection.source.list[0], 'source') !== getSegmentIndexByOrder(this.props.selection.target.list[0], 'target')) {
                disabled = false;
            }
        }
        return <Hotkeys
            keyName="alt+a"
            onKeyDown={this.onClick}>
            <button
                disabled={disabled}
                onClick={this.onClick}>
                Align
            </button>
        </Hotkeys>;
    }

    onClick = () => {
        if (this.props.selection.source.count === 1
            && this.props.selection.target.count === 1) {
            const sourceIndex = getSegmentIndexByOrder(this.props.selection.source.list[0], 'source');
            const targetToOrder = getSegmentByIndex(sourceIndex, 'target').order;

            const log = {
                type: 'target',
                from: this.props.selection.target.list[0],
                to: targetToOrder
            };

            ProjectActions.requireChangeSegmentPosition(log);
            ProjectActions.addSegmentToSelection(-1);
            ProjectActions.onActionHover(null);
        }
    };
}

export default ToolbarActionsAlignComponent;
