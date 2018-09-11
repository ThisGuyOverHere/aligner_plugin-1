import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";

class ToolbarActionsReverseComponent extends Component {
    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {};
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        let reverseDisabled = false;
        //check reverse
        if (
            !((this.props.selection.source.count === 0 && this.props.selection.target.count === 2)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count === 2))
        ) {
            reverseDisabled = true;
        }
        return (
            <button
                disabled={reverseDisabled}
                onClick={this.onReverseClick}
            >
                <i
                    className="icon object ungroup outline"
                >
                </i>
            </button>
        );
    }

    onReverseClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        const segment1 = getSegmentByOrder(this.props.selection[type].list[0], type);
        const segment2 = getSegmentByOrder(this.props.selection[type].list[1], type);
        ProjectActions.reverseTwoSegments(segment1, segment2);
        ProjectActions.addSegmentToSelection(-1);
    };

}

export default ToolbarActionsReverseComponent;