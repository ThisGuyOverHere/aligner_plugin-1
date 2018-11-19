import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import Hotkeys from "react-hot-keys";

class ToolbarActionsSwitchComponent extends Component {
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
        let disabled = false;
        //check reverse
        if (
            !((this.props.selection.source.count === 0 && this.props.selection.target.count === 2)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count === 2))
        ) {
            disabled = true;
        }
        return <Hotkeys
            keyName="alt+r"
            onKeyDown={this.onReverseClick}>
            <button
                disabled={disabled}
                onClick={this.onReverseClick}>
                Switch
            </button>
        </Hotkeys>;
    }

    onReverseClick = () => {
        if (
            ((this.props.selection.source.count === 0 && this.props.selection.target.count === 2)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count === 2))
        ) {
            const type = this.props.selection.source.count > 0 ? 'source' : 'target';
            const segment1 = getSegmentByOrder(this.props.selection[type].list[0], type);
            const segment2 = getSegmentByOrder(this.props.selection[type].list[1], type);
            ProjectActions.reverseTwoSegments(segment1, segment2);
            ProjectActions.addSegmentToSelection(-1);
            ProjectActions.onActionHover(null);
        }
    };

}

export default ToolbarActionsSwitchComponent;
