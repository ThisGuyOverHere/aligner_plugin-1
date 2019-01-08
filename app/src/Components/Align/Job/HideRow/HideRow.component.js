import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";

class HideComponent extends Component {

    static propTypes = {
        index: PropTypes.number.isRequired,
        animate: PropTypes.bool,
        setAnimatedRow: PropTypes.func,
        scrollY: PropTypes.any,
        enableDrag: PropTypes.bool,
        rec: PropTypes.any,
        row: PropTypes.object.isRequired,
        selection: PropTypes.object,
    };

    constructor(props) {
        super(props);

        this.state = {

        };
    }


    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    componentDidMount() {

    }

    componentWillUnmount() {

    }

    render() {
        return <div className="container ui hide-row-container">
            Hide
            <div className="show-toggle" onClick={this.showRow}>
                Show
            </div>
        </div>
    }

    showRow = () => {
        const matches = [{
            source: this.props.row.source.order,
            target: this.props.row.target.order
        }];

        ProjectActions.showSegments(matches);
    }

}

export default HideComponent;
