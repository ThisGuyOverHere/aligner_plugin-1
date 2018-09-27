import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import {Popup} from "semantic-ui-react";

class ToolbarActionsReverseComponent extends Component {
    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            type: 'reverse'
        };
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
        return <span>
                <button
                    disabled={disabled}
                    onMouseOut={this.onMouseLeave}
                    onMouseOver={this.onHover}
                    onClick={this.onReverseClick}>
                    <i className="icon sync"></i>
                    reverse
                </button>
            </span>;
        /*
         return (
             <Popup trigger={comp} content='shortcut alt+R' on='hover' inverted />

         );

         */
    }

    onReverseClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        const segment1 = getSegmentByOrder(this.props.selection[type].list[0], type);
        const segment2 = getSegmentByOrder(this.props.selection[type].list[1], type);
        ProjectActions.reverseTwoSegments(segment1, segment2);
        ProjectActions.addSegmentToSelection(-1);
        ProjectActions.onActionHover(null);
    };

    onHover = () => {
        ProjectActions.onActionHover(this.state.type);
    };

    onMouseLeave = () => {
        ProjectActions.onActionHover(null);
    };

}

export default ToolbarActionsReverseComponent;